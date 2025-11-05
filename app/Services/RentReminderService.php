<?php
// app/Services/RentReminderService.php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Apartment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RentReminderService
{
    protected $smsService;

    public function __construct(EgoSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send daily rent reminders to tenants with due payments
     */
    public function sendDailyRentReminders()
    {
        $today = now();
        $currentMonth = $today->format('Y-m');
        
        // Get tenants who haven't paid for current month
        $tenantsWithDueRent = Tenant::whereHas('apartment')
            ->with(['apartment', 'payments' => function($query) use ($currentMonth) {
                $query->where('month', 'like', $currentMonth . '%')
                      ->where('status', 'paid');
            }])
            ->get()
            ->filter(function($tenant) use ($currentMonth) {
                // Check if tenant has paid for current month
                $hasPaid = $tenant->payments->isNotEmpty();
                return !$hasPaid && $tenant->phone;
            });

        $sentCount = 0;
        $failedCount = 0;

        foreach ($tenantsWithDueRent as $tenant) {
            $success = $this->sendRentReminderToTenant($tenant);
            
            if ($success) {
                $sentCount++;
                
                // Log the reminder
                Log::info('Rent reminder sent', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $this->sanitizeForLog($tenant->name),
                    'phone' => $this->maskPhoneNumber($tenant->phone),
                    'apartment' => $tenant->apartment->number,
                    'month' => $currentMonth
                ]);
            } else {
                $failedCount++;
                
                Log::error('Failed to send rent reminder', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $this->sanitizeForLog($tenant->name),
                    'phone' => $this->maskPhoneNumber($tenant->phone)
                ]);
            }
        }

        return [
            'sent' => $sentCount,
            'failed' => $failedCount,
            'total' => $tenantsWithDueRent->count()
        ];
    }

    /**
     * Send reminder to specific tenant
     */
    public function sendRentReminderToTenant(Tenant $tenant)
    {
        $apartment = $tenant->apartment;
        $currentMonth = now()->format('F Y');
        $dueDate = $this->calculateDueDate($tenant);
        
        $message = $this->buildRentReminderMessage($tenant, $apartment, $currentMonth, $dueDate);
        
        $response = $this->smsService->sendSms($tenant->phone, $message);
        
        return $this->smsService->isSuccess($response);
    }

    /**
     * Build the reminder message with sanitized content
     */
    private function buildRentReminderMessage(Tenant $tenant, Apartment $apartment, $month, $dueDate)
    {
        $sanitizedName = $this->sanitizeForSms($tenant->name);
        $sanitizedApartment = $this->sanitizeForSms($apartment->number);
        $rent = number_format($apartment->rent);
        $sanitizedMonth = $this->sanitizeForSms($month);
        $sanitizedDueDate = $this->sanitizeForSms($dueDate);
        
        $message = "Hello {$sanitizedName}, rent reminder for {$sanitizedMonth}. " .
                  "Apartment {$sanitizedApartment}: UGX {$rent}. " .
                  "Due date: {$sanitizedDueDate}. " .
                  "Late fees apply after due date. Thank you! - PhilWil Apartments";
        
        // Ensure message doesn't exceed SMS limits
        return $this->truncateMessage($message, 160);
    }

    /**
     * Calculate due date based on tenant's billing day
     */
    private function calculateDueDate(Tenant $tenant)
    {
        $billingDay = $tenant->billing_day ?? 5; // Default to 5th of month
        $currentMonth = now();
        
        return $currentMonth->copy()->day($billingDay)->format('jS F');
    }

    /**
     * Send payment confirmation SMS
     */
    public function sendPaymentConfirmation(Payment $payment)
    {
        $tenant = $payment->tenant;
        $apartment = $payment->apartment;
        
        $sanitizedName = $this->sanitizeForSms($tenant->name);
        $sanitizedApartment = $this->sanitizeForSms($apartment->number);
        $amount = number_format($payment->amount);
        $month = Carbon::parse($payment->month)->format('F Y');
        
        $message = "Hello {$sanitizedName}, payment confirmed! " .
                  "UGX {$amount} for {$sanitizedApartment} - " .
                  "{$month}. Thank you! - PhilWil Apartments";
        
        $message = $this->truncateMessage($message, 160);
        $response = $this->smsService->sendSms($tenant->phone, $message);
        
        return $this->smsService->isSuccess($response);
    }

    /**
     * Send late fee notification
     */
    public function sendLateFeeNotification(Tenant $tenant, $lateFeeAmount)
    {
        $sanitizedName = $this->sanitizeForSms($tenant->name);
        $amount = number_format($lateFeeAmount);
        
        $message = "Hello {$sanitizedName}, late fee of UGX {$amount} " .
                  "has been applied for overdue rent. " .
                  "Please contact management. - PhilWil Apartments";
        
        $message = $this->truncateMessage($message, 160);
        $response = $this->smsService->sendSms($tenant->phone, $message);
        
        return $this->smsService->isSuccess($response);
    }

    /**
     * Sanitize text for SMS (remove special characters that might break SMS)
     */
    private function sanitizeForSms($text)
    {
        if (empty($text)) {
            return 'Tenant';
        }
        
        // Remove or replace problematic characters for SMS
        $cleaned = preg_replace('/[^\w\s\-.,!?@#\$%&*()+=\/:;\'"<>]/', '', $text);
        
        // Remove extra whitespace
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        
        return trim($cleaned);
    }

    /**
     * Sanitize text for logs (prevent log injection attacks)
     */
    private function sanitizeForLog($text)
    {
        if (empty($text)) {
            return 'Unknown';
        }
        
        // Remove newlines and control characters that could break logs
        $cleaned = preg_replace('/[\r\n\t\f\v]/', ' ', $text);
        
        // Remove potentially dangerous characters
        $cleaned = preg_replace('/[\[\]{}()<>]/', '', $cleaned);
        
        return trim($cleaned);
    }

    /**
     * Mask phone number for logs (privacy protection)
     */
    private function maskPhoneNumber($phone)
    {
        if (empty($phone) || strlen($phone) < 6) {
            return '***';
        }
        
        // Show only first 3 and last 3 digits: 256707208914 → 256***914
        $prefix = substr($phone, 0, 3);
        $suffix = substr($phone, -3);
        
        return $prefix . '***' . $suffix;
    }

    /**
     * Truncate message to fit SMS limits
     */
    private function truncateMessage($message, $maxLength = 160)
    {
        if (strlen($message) <= $maxLength) {
            return $message;
        }
        
        // Smart truncation - try to break at sentence or word boundary
        $truncated = substr($message, 0, $maxLength - 3);
        
        // Find last space to avoid breaking words
        $lastSpace = strrpos($truncated, ' ');
        if ($lastSpace > ($maxLength - 20)) { // Only if reasonable
            $truncated = substr($truncated, 0, $lastSpace);
        }
        
        return $truncated . '...';
    }

    /**
     * Validate phone number format
     */
    private function isValidPhoneNumber($phone)
    {
        if (empty($phone)) {
            return false;
        }
        
        // Basic validation for Uganda numbers
        $cleaned = preg_replace('/\D/', '', $phone);
        
        return strlen($cleaned) >= 9 && strlen($cleaned) <= 12;
    }
}