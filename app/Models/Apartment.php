<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'rooms',
        'rent',
        'tenant_id',
        'landlord_id',
        'location',
        'status'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function landlord()
    {
        return $this->belongsTo(Landlord::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * FIXED: Get payment status showing FULL amount paid in the month money was received
     */
   public function getPaymentStatusForReport($month)
{
    if (!$this->tenant) {
        return [
            'status' => 'VACANT',
            'amount_paid' => 0,
            'is_advance' => false,
            'months_covered' => 0,
            'payment_made_this_month' => false,
            'is_partial' => false,
            'advance_balance' => 0 // NEW: Track remaining advance
        ];
    }

    $monthCarbon = Carbon::createFromFormat('Y-m', $month);
    $monthFormatted = $monthCarbon->format('Y-m');
    
    // Get ALL payments for this apartment
    $allPayments = $this->payments()
        ->where('status', 'paid')
        ->get();
    
    // Calculate total advance balance that hasn't been allocated yet
    $totalAdvanceBalance = 0;
    $allocatedMonths = [];
    
    foreach ($allPayments as $payment) {
        if ($payment->is_advance_payment) {
            // Calculate how much of this advance hasn't been used
            $paymentAmount = $payment->original_amount ?? $payment->amount;
            $allocatedAmount = 0;
            
            if ($payment->allocated_months) {
                // Calculate how much has been allocated to specific months
                foreach ($payment->allocated_months as $allocatedMonth) {
                    $allocatedAmount += $this->rent; // Each month gets full rent
                }
            }
            
            $remainingAdvance = $paymentAmount - $allocatedAmount;
            if ($remainingAdvance > 0) {
                $totalAdvanceBalance += $remainingAdvance;
            }
        }
    }
    
    // Check if this specific month is covered by any payment
    $coveringPayments = $allPayments->filter(function($payment) use ($monthFormatted) {
        return $payment->coversMonth($monthFormatted);
    });

    $paymentMadeThisMonth = $coveringPayments->filter(function($payment) use ($monthCarbon) {
        $paidAt = $payment->actual_payment_date ?? $payment->paid_at;
        return $paidAt && 
               $paidAt->year == $monthCarbon->year && 
               $paidAt->month == $monthCarbon->month;
    })->isNotEmpty();

    // Calculate amount paid FOR THIS SPECIFIC MONTH
    $amountForThisMonth = 0;
    $isCoveredByAdvance = false;
    $advanceMonthsCovered = 0;
    
    foreach ($coveringPayments as $payment) {
        if ($payment->is_advance_payment) {
            $isCoveredByAdvance = true;
            
            // Check how many months this advance covers
            if ($payment->allocated_months) {
                $advanceMonthsCovered = max($advanceMonthsCovered, count($payment->allocated_months));
            }
            
            // If this month is explicitly in allocated_months, it gets full rent
            if ($payment->allocated_months && in_array($monthFormatted, $payment->allocated_months)) {
                $amountForThisMonth = $this->rent;
            }
        } else {
            // Regular monthly payment
            $amountForThisMonth += $payment->amount;
        }
    }
    
    $rent = $this->rent;
    $status = 'UNPAID';
    $isPartial = false;
    
    // Determine status
    if ($amountForThisMonth >= $rent) {
        $status = 'PAID';
    } elseif ($amountForThisMonth > 0) {
        $status = 'PAID'; // Still considered paid even if partial
        $isPartial = true;
    } elseif ($isCoveredByAdvance && $totalAdvanceBalance >= $rent) {
        // This month is covered by advance balance
        $status = 'PAID';
        $isCoveredByAdvance = true;
        $amountForThisMonth = $rent; // Show as paid with advance
    }

    return [
        'status' => $status,
        'amount_paid' => $amountForThisMonth,
        'is_advance' => $isCoveredByAdvance,
        'months_covered' => $advanceMonthsCovered,
        'payment_made_this_month' => $paymentMadeThisMonth,
        'is_partial' => $isPartial,
        'advance_balance' => $totalAdvanceBalance,
        'remaining_advance' => $totalAdvanceBalance - ($isCoveredByAdvance ? $rent : 0)
    ];
}

    /**
     * Get the payment record for displaying in report
     */
    public function getPaymentForMonth($month)
    {
        $monthFormatted = Carbon::createFromFormat('Y-m', $month)->format('Y-m');
        
        // Get the first payment that covers this month
        $payment = $this->payments()
            ->where('status', 'paid')
            ->get()
            ->first(function($payment) use ($monthFormatted) {
                return $payment->coversMonth($monthFormatted);
            });

        return $payment;
    }

    public function calculateExpectedRent($month = null)
{
    $month = $month ? Carbon::parse($month) : now();
    $monthFormatted = $month->format('Y-m');
    
    // If apartment is vacant, expected rent is 0
    if (!$this->tenant) {
        return 0;
    }
    
    // Check if there's an advance payment covering this month
    $advancePayment = $this->payments()
        ->where('status', 'paid')
        ->where('is_advance_payment', true)
        ->get()
        ->first(function($payment) use ($monthFormatted) {
            return $payment->coversMonth($monthFormatted);
        });
    
    // If this month is covered by advance payment (and payment was made earlier),
    // expected rent for THIS month is 0 (since it was already paid)
    if ($advancePayment) {
        $paidAt = $advancePayment->actual_payment_date ?? $advancePayment->paid_at;
        if ($paidAt && $paidAt->month != $month->month) {
            return 0; // Already paid in advance
        }
    }
    
    // Otherwise, expected rent is the apartment's monthly rent
    return $this->rent;
}
}