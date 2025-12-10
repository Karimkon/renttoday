<?php
// app/Http/Controllers/Admin/SmsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Payment;
use App\Models\Apartment;
use App\Services\RentReminderService;
use App\Services\EgoSmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    protected $reminderService;
    protected $smsService;

    public function __construct(RentReminderService $reminderService, EgoSmsService $smsService)
    {
        $this->reminderService = $reminderService;
        $this->smsService = $smsService;
    }

    /**
     * Show bulk SMS form
     */
    public function showBulkSmsForm(Request $request)
    {
        $months = $this->getAvailableMonths();
        $selectedMonth = $request->month ?? now()->format('Y-m');
        $selectedStatus = $request->status ?? 'unpaid';
        
        // Get tenants based on filters
        $tenants = $this->getFilteredTenants($selectedMonth, $selectedStatus);
        
        return view('admin.sms.bulk-sms', compact('months', 'selectedMonth', 'selectedStatus', 'tenants'));
    }

    /**
     * Get filtered tenants with detailed payment status
     */
    private function getFilteredTenants($month, $status = 'unpaid')
    {
        $tenants = Tenant::whereHas('apartment')
            ->with(['apartment', 'payments' => function($query) use ($month) {
                $query->where('status', 'paid')
                      ->where(function($q) use ($month) {
                          // Payments allocated to this specific month
                          $q->whereRaw("DATE_FORMAT(month, '%Y-%m') = ?", [$month])
                            ->orWhereJsonContains('allocated_months', $month);
                      });
            }])
            ->whereNotNull('phone')
            ->get()
            ->map(function($tenant) use ($month) {
                $apartment = $tenant->apartment;
                
                if (!$apartment) {
                    $tenant->hasPaid = false;
                    $tenant->paymentStatus = 'unpaid';
                    $tenant->dueAmount = 0;
                    $tenant->paidAmount = 0;
                    $tenant->balance = 0;
                    $tenant->percentagePaid = 0;
                    return $tenant;
                }
                
                $rentAmount = $apartment->rent;
                $totalPaid = $tenant->payments->sum('amount');
                
                // Calculate payment details
                $tenant->dueAmount = $rentAmount;
                $tenant->paidAmount = $totalPaid;
                $tenant->balance = max(0, $rentAmount - $totalPaid);
                $tenant->percentagePaid = $rentAmount > 0 ? round(($totalPaid / $rentAmount) * 100) : 0;
                
                // Determine payment status with thresholds
                if ($totalPaid == 0) {
                    $tenant->paymentStatus = 'unpaid';
                    $tenant->hasPaid = false;
                    $tenant->isPartial = false;
                } elseif ($totalPaid >= $rentAmount) {
                    $tenant->paymentStatus = 'paid';
                    $tenant->hasPaid = true;
                    $tenant->isPartial = false;
                } elseif ($totalPaid >= ($rentAmount * 0.5)) { // At least 50% paid
                    $tenant->paymentStatus = 'partial_high';
                    $tenant->hasPaid = true;
                    $tenant->isPartial = true;
                } else {
                    $tenant->paymentStatus = 'partial_low';
                    $tenant->hasPaid = true;
                    $tenant->isPartial = true;
                }
                
                // Get payment dates
                $tenant->lastPaymentDate = $tenant->payments->sortByDesc('created_at')->first()->created_at ?? null;
                $tenant->paymentCount = $tenant->payments->count();
                
                return $tenant;
            });

        // Filter based on selected status
        if ($status === 'unpaid') {
            return $tenants->where('paymentStatus', 'unpaid')->values();
        } elseif ($status === 'partial') {
            return $tenants->whereIn('paymentStatus', ['partial_low', 'partial_high'])->values();
        } elseif ($status === 'partial_high') {
            return $tenants->where('paymentStatus', 'partial_high')->values();
        } elseif ($status === 'partial_low') {
            return $tenants->where('paymentStatus', 'partial_low')->values();
        } elseif ($status === 'paid') {
            return $tenants->where('paymentStatus', 'paid')->values();
        } elseif ($status === 'all') {
            return $tenants->values();
        }
        
        return $tenants;
    }

    /**
     * Send bulk SMS to selected tenants
     */
    public function sendBulkSms(Request $request)
    {
        session()->forget(['sent_messages', 'failed_messages']);
        
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'message_type' => 'required|in:reminder,partial_reminder,balance_reminder,custom',
            'custom_message' => 'nullable|string|max:160',
            'tenant_ids' => 'required|array|min:1',
            'tenant_ids.*' => 'exists:tenants,id'
        ]);

        $selectedMonth = $request->month;
        $messageType = $request->message_type;
        $customMessage = $request->custom_message;
        $selectedTenantIds = $request->tenant_ids ?? [];
        
        // Get tenants with their payment details
        $tenants = Tenant::whereIn('id', $selectedTenantIds)
            ->whereNotNull('phone')
            ->with(['apartment', 'payments' => function($query) use ($selectedMonth) {
                $query->where('status', 'paid')
                      ->where(function($q) use ($selectedMonth) {
                          $q->whereRaw("DATE_FORMAT(month, '%Y-%m') = ?", [$selectedMonth])
                            ->orWhereJsonContains('allocated_months', $selectedMonth);
                      });
            }])
            ->get()
            ->map(function($tenant) use ($selectedMonth) {
                $apartment = $tenant->apartment;
                $rentAmount = $apartment ? $apartment->rent : 0;
                $totalPaid = $tenant->payments->sum('amount');
                
                $tenant->dueAmount = $rentAmount;
                $tenant->paidAmount = $totalPaid;
                $tenant->balance = max(0, $rentAmount - $totalPaid);
                
                // Determine payment status
                if ($totalPaid == 0) {
                    $tenant->paymentStatus = 'unpaid';
                } elseif ($totalPaid >= $rentAmount) {
                    $tenant->paymentStatus = 'paid';
                } else {
                    $tenant->paymentStatus = 'partial';
                }
                
                return $tenant;
            });

        if ($tenants->isEmpty()) {
            return redirect()->back()
                ->with('error', 'No valid tenants selected for SMS sending.')
                ->withInput();
        }

        $sentCount = 0;
        $failedCount = 0;
        $sentMessages = [];
        $failedMessages = [];

        foreach ($tenants as $tenant) {
            try {
                if (!$tenant->phone) {
                    $failedCount++;
                    $failedMessages[] = [
                        'tenant' => $tenant->name,
                        'reason' => 'No phone number',
                        'apartment' => $tenant->apartment->number ?? 'N/A',
                        'status' => $tenant->paymentStatus
                    ];
                    continue;
                }

                // Build appropriate message based on type and payment status
                $message = $this->buildMessage($tenant, $selectedMonth, $messageType, $customMessage);

                // Send SMS
                $response = $this->smsService->sendSms($tenant->phone, $message);
                
                if ($this->smsService->isSuccess($response)) {
                    $sentCount++;
                    $sentMessages[] = [
                        'tenant' => $tenant->name,
                        'phone' => $this->maskPhone($tenant->phone),
                        'message' => $message,
                        'apartment' => $tenant->apartment->number ?? 'N/A',
                        'status' => $tenant->paymentStatus,
                        'paid' => number_format($tenant->paidAmount),
                        'balance' => number_format($tenant->balance)
                    ];
                    
                    Log::info('Bulk SMS sent', [
                        'tenant_id' => $tenant->id,
                        'phone' => $this->maskPhone($tenant->phone),
                        'month' => $selectedMonth,
                        'payment_status' => $tenant->paymentStatus,
                        'amount_paid' => $tenant->paidAmount,
                        'balance' => $tenant->balance
                    ]);
                } else {
                    $failedCount++;
                    $failedMessages[] = [
                        'tenant' => $tenant->name,
                        'reason' => 'SMS API failed',
                        'response' => $response,
                        'apartment' => $tenant->apartment->number ?? 'N/A',
                        'status' => $tenant->paymentStatus
                    ];
                }
                
                // Small delay to avoid rate limiting
                usleep(100000);
                
            } catch (\Exception $e) {
                $failedCount++;
                $failedMessages[] = [
                    'tenant' => $tenant->name,
                    'reason' => 'Exception: ' . $e->getMessage(),
                    'apartment' => $tenant->apartment->number ?? 'N/A',
                    'status' => $tenant->paymentStatus
                ];
                Log::error('Bulk SMS error', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return redirect()->route('admin.sms.bulk-sms')
            ->with('success', "Successfully sent {$sentCount} SMS messages. Failed: {$failedCount}")
            ->with('sent_messages', $sentMessages)
            ->with('failed_messages', $failedMessages);
    }

    /**
     * Build appropriate message based on type and payment status
     */
    private function buildMessage($tenant, $month, $messageType, $customMessage = null)
    {
        $tenantName = $this->cleanForSms($tenant->name);
        $apartmentNumber = $tenant->apartment ? $this->cleanForSms($tenant->apartment->number) : 'N/A';
        $totalRent = $tenant->dueAmount;
        $paidAmount = $tenant->paidAmount;
        $balance = $tenant->balance;
        $monthName = Carbon::createFromFormat('Y-m', $month)->format('F Y');
        $nextMonth = Carbon::createFromFormat('Y-m', $month)->addMonth()->format('F Y');
        
        if ($messageType === 'custom' && $customMessage) {
            return $this->personalizeMessage($customMessage, $tenant, $month);
        }
        
        if ($messageType === 'partial_reminder' || $messageType === 'balance_reminder') {
            // Specialized partial payment messages
            if ($paidAmount == 0) {
                return "Hello {$tenantName}, rent reminder for {$monthName}. " .
                       "Apartment {$apartmentNumber}: UGX " . number_format($totalRent) . " due. " .
                       "Please pay by 5th {$monthName}. Late fees apply. - PhilWil Apartments";
            } elseif ($balance > 0) {
                return "Hello {$tenantName}, balance reminder for {$monthName}. " .
                       "Apartment {$apartmentNumber}: Paid UGX " . number_format($paidAmount) . ". " .
                       "Balance: UGX " . number_format($balance) . " of UGX " . number_format($totalRent) . ". " .
                       "Please clear balance immediately to avoid penalties. - PhilWil Apartments";
            } else {
                return "Hello {$tenantName}, thank you for full payment for {$monthName}. " .
                       "Apartment {$apartmentNumber}: UGX " . number_format($totalRent) . " received. " .
                       "Next payment due: {$nextMonth}. - PhilWil Apartments";
            }
        }
        
        // Default reminder (adaptive based on payment status)
        if ($paidAmount == 0) {
            return "Hello {$tenantName}, rent for {$monthName} is due. " .
                   "Apartment {$apartmentNumber}: UGX " . number_format($totalRent) . ". " .
                   "Due date: 5th {$monthName}. Late fee: 10% after due date. - PhilWil Apartments";
        } elseif ($balance > 0) {
            $percentagePaid = $totalRent > 0 ? round(($paidAmount / $totalRent) * 100) : 0;
            return "Hello {$tenantName}, partial payment noted for {$monthName}. " .
                   "Apartment {$apartmentNumber}: UGX " . number_format($paidAmount) . " paid ({$percentagePaid}%). " .
                   "Outstanding: UGX " . number_format($balance) . ". " .
                   "Please complete payment by 5th {$monthName}. - PhilWil Apartments";
        } else {
            return "Hello {$tenantName}, payment confirmed for {$monthName}. " .
                   "Apartment {$apartmentNumber}: UGX " . number_format($totalRent) . " received. " .
                   "Thank you for timely payment. Next due: {$nextMonth}. - PhilWil Apartments";
        }
    }

    /**
     * Personalize custom message with tenant details
     */
    private function personalizeMessage($message, $tenant, $month)
    {
        $totalRent = $tenant->dueAmount;
        $paidAmount = $tenant->paidAmount;
        $balance = $tenant->balance;
        $percentagePaid = $totalRent > 0 ? round(($paidAmount / $totalRent) * 100) : 0;
        
        $replacements = [
            '{{tenant_name}}' => $this->cleanForSms($tenant->name),
            '{{tenant_name}}' => $this->cleanForSms($tenant->name),
            '{{apartment_number}}' => $tenant->apartment ? $this->cleanForSms($tenant->apartment->number) : 'N/A',
            '{{rent_amount}}' => number_format($totalRent),
            '{{month}}' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
            '{{phone}}' => $this->maskPhone($tenant->phone),
            '{{balance}}' => number_format($balance),
            '{{paid_amount}}' => number_format($paidAmount),
            '{{percentage_paid}}' => $percentagePaid,
            '{{payment_status}}' => $tenant->paymentStatus,
            '{{due_date}}' => '5th ' . Carbon::createFromFormat('Y-m', $month)->format('F Y'),
            '{{next_month}}' => Carbon::createFromFormat('Y-m', $month)->addMonth()->format('F Y')
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * Get available months with payments
     */
    private function getAvailableMonths()
    {
        try {
            $months = Payment::selectRaw("DATE_FORMAT(month, '%Y-%m') as month")
                ->distinct()
                ->orderBy('month', 'desc')
                ->pluck('month');
                
            // Add current and next 3 months
            $current = now();
            $additionalMonths = collect();
            
            for ($i = -3; $i <= 3; $i++) {
                $month = $current->copy()->addMonths($i)->format('Y-m');
                $additionalMonths->push($month);
            }
            
            // Combine and ensure unique
            $allMonths = $months->concat($additionalMonths)
                ->unique()
                ->filter(function($month) {
                    return preg_match('/^\d{4}-\d{2}$/', $month);
                })
                ->values();
                
            return $allMonths;
            
        } catch (\Exception $e) {
            Log::error('Error getting available months', ['error' => $e->getMessage()]);
            return collect([now()->format('Y-m')]);
        }
    }

    /**
     * Clean text for SMS
     */
    private function cleanForSms($text)
    {
        if (empty($text)) {
            return 'Tenant';
        }
        
        $cleaned = preg_replace('/[^\w\s\-.,!?@#&*():;\'"]/', '', $text);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        
        return trim($cleaned);
    }

    /**
     * Mask phone number
     */
    private function maskPhone($phone)
    {
        if (empty($phone)) {
            return '***';
        }
        
        $cleaned = preg_replace('/\D/', '', $phone);
        if (strlen($cleaned) < 6) {
            return '***';
        }
        
        return substr($cleaned, 0, 3) . '***' . substr($cleaned, -3);
    }
}