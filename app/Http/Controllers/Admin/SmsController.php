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
     * Send bulk SMS to unpaid tenants
     */
   public function sendBulkSms(Request $request)
{
    session()->forget(['sent_messages', 'failed_messages']);
    $request->validate([
        'month' => 'required|date_format:Y-m',
        'message_type' => 'required|in:reminder,custom',
        'custom_message' => 'nullable|string|max:160',
        'tenant_ids' => 'required|array|min:1', // CHANGED: required, min 1
        'tenant_ids.*' => 'exists:tenants,id'
    ]);

    $selectedMonth = $request->month;
    $messageType = $request->message_type;
    $customMessage = $request->custom_message;
    $selectedTenantIds = $request->tenant_ids ?? [];
    
    // IMPORTANT: Only send to selected tenants
    $tenants = Tenant::whereIn('id', $selectedTenantIds)
        ->whereNotNull('phone')
        ->with('apartment')
        ->get();

    // Validate that we found the tenants
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
                    'apartment' => $tenant->apartment->number ?? 'N/A'
                ];
                continue;
            }

            // Build message based on type
            if ($messageType === 'custom' && $customMessage) {
                $message = $this->personalizeMessage($customMessage, $tenant, $selectedMonth);
            } else {
                $message = $this->buildReminderMessage($tenant, $selectedMonth);
            }

            // Send SMS
            $response = $this->smsService->sendSms($tenant->phone, $message);
            
            if ($this->smsService->isSuccess($response)) {
                $sentCount++;
                $sentMessages[] = [
                    'tenant' => $tenant->name,
                    'phone' => $this->maskPhone($tenant->phone),
                    'message' => $message,
                    'apartment' => $tenant->apartment->number ?? 'N/A'
                ];
                
                Log::info('Bulk SMS sent successfully', [
                    'tenant_id' => $tenant->id,
                    'phone' => $this->maskPhone($tenant->phone),
                    'month' => $selectedMonth
                ]);
            } else {
                $failedCount++;
                $failedMessages[] = [
                    'tenant' => $tenant->name,
                    'reason' => 'SMS API failed',
                    'response' => $response,
                    'apartment' => $tenant->apartment->number ?? 'N/A'
                ];
            }
            
            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 second delay
            
        } catch (\Exception $e) {
            $failedCount++;
            $failedMessages[] = [
                'tenant' => $tenant->name,
                'reason' => 'Exception: ' . $e->getMessage(),
                'apartment' => $tenant->apartment->number ?? 'N/A'
            ];
            Log::error('Bulk SMS error for tenant ' . $tenant->id, ['error' => $e->getMessage()]);
        }
    }

    return redirect()->route('admin.sms.bulk-sms')
        ->with('success', "Sent {$sentCount} SMS, failed: {$failedCount}")
        ->with('sent_messages', $sentMessages)
        ->with('failed_messages', $failedMessages);
}

    /**
     * Get tenants who haven't paid for a specific month
     */
    private function getUnpaidTenantsForMonth($month)
    {
        return Tenant::whereHas('apartment')
            ->with(['apartment', 'payments' => function($query) use ($month) {
                $query->where('month', 'like', $month . '%')
                      ->where('status', 'paid');
            }])
            ->whereNotNull('phone')
            ->get()
            ->filter(function($tenant) use ($month) {
                $hasPaid = $tenant->payments->isNotEmpty();
                return !$hasPaid;
            })
            ->values();
    }

    /**
     * Get filtered tenants for display
     */
    private function getFilteredTenants($month, $status = 'unpaid')
    {
        $tenants = Tenant::whereHas('apartment')
            ->with(['apartment', 'payments' => function($query) use ($month) {
                $query->where('month', 'like', $month . '%')
                      ->where('status', 'paid');
            }])
            ->whereNotNull('phone')
            ->get()
            ->map(function($tenant) use ($month) {
                $tenant->hasPaid = $tenant->payments->isNotEmpty();
                $tenant->dueAmount = $tenant->apartment ? $tenant->apartment->rent : 0;
                $tenant->paymentStatus = $tenant->hasPaid ? 'paid' : 'unpaid';
                return $tenant;
            });

        if ($status === 'unpaid') {
            return $tenants->where('paymentStatus', 'unpaid')->values();
        } elseif ($status === 'paid') {
            return $tenants->where('paymentStatus', 'paid')->values();
        }
        
        return $tenants;
    }

    /**
     * Build reminder message
     */
    private function buildReminderMessage($tenant, $month)
    {
        $tenantName = $this->cleanForSms($tenant->name);
        $apartmentNumber = $tenant->apartment ? $this->cleanForSms($tenant->apartment->number) : 'N/A';
        $rent = $tenant->apartment ? number_format($tenant->apartment->rent) : '0';
        $monthName = Carbon::createFromFormat('Y-m', $month)->format('F Y');
        
        return "Hello {$tenantName}, rent reminder for {$monthName}. " .
               "Apartment {$apartmentNumber}: UGX {$rent}. " .
               "Due date: 5th {$monthName}. " .
               "Late fees apply after due date. Thank you! - PhilWil Apartments";
    }

    /**
     * Personalize custom message with tenant details
     */
    private function personalizeMessage($message, $tenant, $month)
    {
        $replacements = [
            '{{tenant_name}}' => $this->cleanForSms($tenant->name),
            '{{apartment_number}}' => $tenant->apartment ? $this->cleanForSms($tenant->apartment->number) : 'N/A',
            '{{rent_amount}}' => $tenant->apartment ? number_format($tenant->apartment->rent) : '0',
            '{{month}}' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
            '{{phone}}' => $this->maskPhone($tenant->phone),
            '{{balance}}' => $tenant->apartment ? number_format($tenant->apartment->rent) : '0'
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

   
/**
 * Get available months with payments - FIXED: Ensure proper Y-m format
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
        
        // Combine and ensure unique, valid Y-m format
        $allMonths = $months->concat($additionalMonths)
            ->unique()
            ->filter(function($month) {
                // Validate that it's in Y-m format
                return preg_match('/^\d{4}-\d{2}$/', $month);
            })
            ->values();
            
        return $allMonths;
        
    } catch (\Exception $e) {
        Log::error('Error getting available months', ['error' => $e->getMessage()]);
        
        // Fallback: return at least current month
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