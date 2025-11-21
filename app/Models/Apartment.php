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
                'is_partial' => false
            ];
        }

        $monthCarbon = Carbon::createFromFormat('Y-m', $month);
        $monthFormatted = $monthCarbon->format('Y-m');
        
        // Get ALL payments for this apartment
        $allPayments = $this->payments()
            ->where('status', 'paid')
            ->get();
        
        // Filter payments that cover this specific month
        $coveringPayments = $allPayments->filter(function($payment) use ($monthFormatted) {
            return $payment->coversMonth($monthFormatted);
        });

        // Check if ANY payment was CREATED (actual money received) in this month
        $paymentsCreatedThisMonth = $coveringPayments->filter(function($payment) use ($monthCarbon) {
            $paidAt = $payment->actual_payment_date ?? $payment->paid_at;
            return $paidAt && 
                   $paidAt->year == $monthCarbon->year && 
                   $paidAt->month == $monthCarbon->month;
        });

        $paymentMadeThisMonth = $paymentsCreatedThisMonth->isNotEmpty();

        // FIXED: If payment was made this month, show the FULL original_amount
        // Otherwise, show the regular monthly rent amount
        $totalPaid = 0;
        if ($paymentMadeThisMonth) {
            // Get the advance payment that was made this month
            $advancePayment = $paymentsCreatedThisMonth->first(function($payment) {
                return $payment->is_advance_payment && $payment->original_amount;
            });
            
            if ($advancePayment) {
                // Show the FULL amount that was actually paid (e.g., 740,000)
                $totalPaid = $advancePayment->original_amount;
            } else {
                // Regular payments - sum them up
                $totalPaid = $paymentsCreatedThisMonth->sum('amount');
            }
        } else {
            // This month is covered by advance - show 0 or regular rent
            $totalPaid = $coveringPayments->sum(function($payment) use ($monthFormatted) {
                return $payment->getAmountForMonth($monthFormatted);
            });
        }

        // Check for advance payments
        $advancePayments = $coveringPayments->where('is_advance_payment', true);
        $isAdvance = $advancePayments->isNotEmpty();
        
        // Calculate remaining months covered
        $monthsCovered = 0;
        if ($isAdvance) {
            foreach ($advancePayments as $payment) {
                if ($payment->allocated_months) {
                    // Count how many months this advance covers
                    $monthsCovered = max($monthsCovered, count($payment->allocated_months));
                }
            }
        }

        $rent = $this->rent;
        $status = 'UNPAID';
        $isPartial = false;

        if ($totalPaid >= $rent) {
            $status = 'PAID';
            // Check if it's a partial payment (less than rent but greater than 0)
            if ($paymentMadeThisMonth && $totalPaid > 0 && $totalPaid < $rent) {
                $isPartial = true;
            }
        } elseif ($totalPaid > 0) {
            $status = 'PAID';
            $isPartial = true;
        }

        return [
            'status' => $status,
            'amount_paid' => $totalPaid,
            'is_advance' => $isAdvance,
            'months_covered' => $monthsCovered,
            'payment_made_this_month' => $paymentMadeThisMonth,
            'is_partial' => $isPartial
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
}