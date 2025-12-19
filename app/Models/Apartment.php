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
     * Get the ACTUAL amount paid in a specific calendar month.
     * Uses a unique filter to avoid double-counting the same payment record.
     *
     * @param string $month Format: Y-m (e.g., '2025-01')
     * @return float
     */
    public function getActualAmountPaidInMonth($month)
    {
        $payments = $this->getPaymentsActuallyMadeInMonth($month);
        return $payments->sum('amount');
    }

    /**
 * Get payments that were ACTUALLY made in a specific calendar month
 * (not allocated to that month, but physically received)
 * 
 * @param string $month Format: Y-m (e.g., '2025-01')
 * @return \Illuminate\Database\Eloquent\Collection
 */
public function getPaymentsActuallyMadeInMonth($month)
{
    return $this->payments()
        ->select('payments.*')
        ->where('status', 'paid')
        ->where(function($query) use ($month) {
            $query->whereRaw("DATE_FORMAT(actual_payment_date, '%Y-%m') = ?", [$month])
                  ->orWhere(function($q) use ($month) {
                      $q->whereNull('actual_payment_date')
                        ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') = ?", [$month]);
                  });
        })
        ->get()
        ->unique('id')
        ->values(); // REMOVED the coversMonth filter!
}

    /**
     * NEW METHOD: Check if this month is covered by a PREVIOUS advance payment
     * (advance paid in an earlier month that covers this month)
     * 
     * @param string $month Format: Y-m (e.g., '2025-01')
     * @return Payment|null
     */
    public function getCoveringAdvancePayment($month)
    {
        return $this->payments()
            ->where('status', 'paid')
            ->where('is_advance_payment', true)
            ->whereJsonContains('allocated_months', $month)
            ->where(function($query) use ($month) {
                // Payment was made in a DIFFERENT month (earlier)
                $query->whereRaw("DATE_FORMAT(actual_payment_date, '%Y-%m') != ?", [$month])
                      ->orWhere(function($q) use ($month) {
                          $q->whereNull('actual_payment_date')
                            ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') != ?", [$month]);
                      });
            })
            ->first();
    }

    /**
     * UPDATED: Get payment status for reports
     * Now includes information about whether money was actually paid this month
     */
    public function getPaymentStatusForReport($month)
    {
        if (!$this->tenant) {
            return [
                'status' => 'VACANT',
                'amount_paid' => 0,
                'actual_amount_this_month' => 0, // NEW: Actual cash received this month
                'is_advance' => false,
                'months_covered' => 0,
                'payment_made_this_month' => false,
                'is_partial' => false,
                'advance_balance' => 0,
                'remaining_advance' => 0,
                'is_covered' => false,
                'covered_by_previous_advance' => false // NEW
            ];
        }

        $monthCarbon = Carbon::createFromFormat('Y-m', $month);
        $monthFormatted = $monthCarbon->format('Y-m');
        
        // Get payments ACTUALLY made this month (for commission)
        $paymentsActuallyMadeThisMonth = $this->getPaymentsActuallyMadeInMonth($monthFormatted);
        $actualAmountThisMonth = $paymentsActuallyMadeThisMonth->sum('amount');
        
        // Check if covered by a previous advance
        $coveringAdvance = $this->getCoveringAdvancePayment($monthFormatted);
        $coveredByPreviousAdvance = ($coveringAdvance !== null);
        
        // Get ALL payments for this apartment to check coverage
        $allPayments = $this->payments()
            ->where('status', 'paid')
            ->get();
        
        // Check if ANY payment covers this month
        $isCovered = false;
        $amountForThisMonth = 0;
        $isAdvance = false;
        $monthsCovered = 0;
        $paymentMadeThisMonth = false;
        
        foreach ($allPayments as $payment) {
            if ($payment->coversMonth($monthFormatted)) {
                $isCovered = true;
                $amountForThisMonth += $payment->getAmountForMonth($monthFormatted);
                
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
        
        // Check tenant credit balance
        if (!$isCovered && ($this->tenant->credit_balance ?? 0) >= $this->rent) {
            $isCovered = true;
            $amountForThisMonth = $this->rent;
        }
        
        $rent = $this->rent;
        $status = 'UNPAID';
        $isPartial = false;
        
        if ($isCovered) {
            if ($amountForThisMonth >= $rent) {
                $status = 'PAID';
            } elseif ($amountForThisMonth > 0) {
                $status = 'PAID';
                $isPartial = true;
            }
        }

        return [
            'status' => $status,
            'amount_paid' => $amountForThisMonth, // Amount covering this specific month
            'actual_amount_this_month' => $actualAmountThisMonth, // NEW: Actual cash received this month (for commission)
            'is_covered' => $isCovered,
            'covered_by_previous_advance' => $coveredByPreviousAdvance, // NEW
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
        
        if (!$this->tenant) {
            return 0;
        }
        
        $advancePayment = $this->payments()
            ->where('status', 'paid')
            ->where('is_advance_payment', true)
            ->get()
            ->first(function($payment) use ($monthFormatted) {
                return $payment->coversMonth($monthFormatted);
            });
        
        if ($advancePayment) {
            $paidAt = $advancePayment->actual_payment_date ?? $advancePayment->paid_at;
            if ($paidAt && $paidAt->month != $month->month) {
                return 0;
            }
        }
        
        return $this->rent;
    }

    // In Apartment model
public function getPaymentsCoveringMonth($month)
{
    return $this->payments()
        ->where('status', 'paid')
        ->get()
        ->filter(function($payment) use ($month) {
            return $payment->coversMonth($month);
        });
}

public function getTotalAmountCoveringMonth($month)
{
    $payments = $this->getPaymentsCoveringMonth($month);
    return $payments->sum(function($payment) use ($month) {
        return $payment->getAmountForMonth($month);
    });
}
}