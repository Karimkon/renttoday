<?php

// Create App\Services\AdvancePaymentService.php

namespace App\Services;

use App\Models\Payment;
use App\Models\Apartment;
use Carbon\Carbon;

class AdvancePaymentService
{
    /**
     * Process an advance payment and allocate it to months
     */
    public function processAdvancePayment(Payment $payment)
    {
        if (!$payment->is_advance_payment || !$payment->apartment) {
            return $payment;
        }
        
        $apartment = $payment->apartment;
        $rent = $apartment->rent;
        $originalAmount = $payment->original_amount ?? $payment->amount;
        
        // Calculate coverage
        $fullMonths = floor($originalAmount / $rent);
        $remainder = $originalAmount % $rent;
        
        // Get existing advance payments for this apartment
        $existingAdvances = Payment::where('apartment_id', $apartment->id)
            ->where('is_advance_payment', true)
            ->where('status', 'paid')
            ->get();
        
        // Find first unpaid month
        $startMonth = $this->findFirstUnpaidMonth($apartment);
        
        // Allocate months
        $allocatedMonths = [];
        for ($i = 0; $i < $fullMonths; $i++) {
            $monthToAllocate = $startMonth->copy()->addMonths($i)->format('Y-m');
            $allocatedMonths[] = $monthToAllocate;
        }
        
        $payment->allocated_months = $allocatedMonths;
        
        // Handle remainder
        if ($remainder > 0) {
            // Store remainder for future allocation
            $nextMonth = $startMonth->copy()->addMonths($fullMonths);
            $payment->notes = "Remaining advance: UGX " . number_format($remainder) . 
                            " for " . $nextMonth->format('F Y');
        }
        
        $payment->save();
        
        return $payment;
    }
    
    /**
     * Find the first unpaid month for an apartment
     */
    private function findFirstUnpaidMonth(Apartment $apartment)
    {
        // Start from current month
        $currentMonth = Carbon::now()->startOfMonth();
        
        // Check up to 12 months ahead
        for ($i = 0; $i < 12; $i++) {
            $checkMonth = $currentMonth->copy()->addMonths($i);
            $monthFormatted = $checkMonth->format('Y-m');
            
            $paymentStatus = $apartment->getPaymentStatusForReport($monthFormatted);
            
            if ($paymentStatus['status'] !== 'PAID' && $paymentStatus['amount_paid'] < $apartment->rent) {
                return $checkMonth;
            }
        }
        
        // If all paid, return next month
        return $currentMonth->copy()->addMonths(12);
    }
    
    /**
     * Get advance balance for an apartment
     */
    public function getAdvanceBalance(Apartment $apartment, $asOfMonth = null)
    {
        if (!$asOfMonth) {
            $asOfMonth = Carbon::now()->format('Y-m');
        }
        
        $asOfDate = Carbon::createFromFormat('Y-m', $asOfMonth);
        
        $advancePayments = Payment::where('apartment_id', $apartment->id)
            ->where('is_advance_payment', true)
            ->where('status', 'paid')
            ->get();
        
        $totalAdvance = 0;
        $totalAllocated = 0;
        
        foreach ($advancePayments as $payment) {
            $paymentAmount = $payment->original_amount ?? $payment->amount;
            $totalAdvance += $paymentAmount;
            
            if ($payment->allocated_months) {
                foreach ($payment->allocated_months as $allocatedMonth) {
                    if ($allocatedMonth <= $asOfDate->format('Y-m')) {
                        $totalAllocated += $apartment->rent;
                    }
                }
            }
        }
        
        return [
            'total_advance' => $totalAdvance,
            'total_allocated' => $totalAllocated,
            'available_balance' => max(0, $totalAdvance - $totalAllocated),
            'can_cover_months' => floor(max(0, $totalAdvance - $totalAllocated) / $apartment->rent),
            'remainder' => max(0, $totalAdvance - $totalAllocated) % $apartment->rent
        ];
    }
}