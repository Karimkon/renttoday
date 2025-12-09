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
            'advance_balance' => 0,
            'remaining_advance' => 0,
            'is_covered' => false // ADD THIS
        ];
    }

    $monthCarbon = Carbon::createFromFormat('Y-m', $month);
    $monthFormatted = $monthCarbon->format('Y-m');
    
    // Get ALL payments for this apartment
    $allPayments = $this->payments()
        ->where('status', 'paid')
        ->get();
    
    // Check if ANY payment covers this month (regardless of when it was paid)
    $isCovered = false; // ADD THIS LINE
    $amountForThisMonth = 0;
    $isAdvance = false;
    $monthsCovered = 0;
    $paymentMadeThisMonth = false;
    
    foreach ($allPayments as $payment) {
        // Check if payment covers this month
        if ($payment->coversMonth($monthFormatted)) {
            $isCovered = true; // SET THIS
            
            // For this specific month, get the amount allocated
            $amountForThisMonth += $payment->getAmountForMonth($monthFormatted);
            
            // Check if payment was made in this month
            $paidAt = $payment->actual_payment_date ?? $payment->paid_at;
            if ($paidAt && $paidAt->format('Y-m') === $monthFormatted) {
                $paymentMadeThisMonth = true;
            }
            
            if ($payment->is_advance_payment) {
                $isAdvance = true;
                if ($payment->allocated_months && in_array($monthFormatted, $payment->allocated_months)) {
                    $monthsCovered = count($payment->allocated_months);
                }
            }
        }
    }
    
    // Also check tenant credit balance
    if (!$isCovered && ($this->tenant->credit_balance ?? 0) >= $this->rent) {
        $isCovered = true;
        $amountForThisMonth = $this->rent;
    }
    
    $rent = $this->rent;
    $status = 'UNPAID';
    $isPartial = false;
    
    // Determine status
    if ($isCovered) {
        if ($amountForThisMonth >= $rent) {
            $status = 'PAID';
        } elseif ($amountForThisMonth > 0) {
            $status = 'PAID'; // Still show as PAID but mark as partial
            $isPartial = true;
        }
    }

    return [
        'status' => $status,
        'amount_paid' => $amountForThisMonth, // Amount covering this specific month
        'is_covered' => $isCovered, // ADD THIS
        'is_advance' => $isAdvance,
        'months_covered' => $monthsCovered,
        'payment_made_this_month' => $paymentMadeThisMonth,
        'is_partial' => $isPartial,
        'advance_balance' => 0,
        'remaining_advance' => 0
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