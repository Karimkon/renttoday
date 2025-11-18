<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * FIXED: Get payment status for report - Uses MONTH field to determine which report to show payment in
     */
    public function getPaymentStatusForReport($month)
    {
        if (!$this->tenant) {
            return [
                'status' => 'VACANT',
                'amount_paid' => 0,
                'is_partial' => false,
                'is_advance' => false,
                'months_covered' => 0,
                'payment_made_this_month' => false
            ];
        }

        // 1. Check if there's a payment FOR this specific month (using the 'month' field)
        $paymentForThisMonth = $this->getPaymentForMonth($month);
        
        if ($paymentForThisMonth) {
            // There's a payment specifically FOR this month
            $totalPaidForThisMonth = $paymentForThisMonth->amount;
            $isPartial = ($totalPaidForThisMonth < $this->rent);
            
            // Check if this was an advance payment (paid before this month)
            $paymentDate = \Carbon\Carbon::parse($paymentForThisMonth->created_at);
            $targetDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $wasPaidEarly = $paymentDate->lt($targetDate);
            
            return [
                'status' => 'PAID',
                'amount_paid' => $totalPaidForThisMonth,
                'is_partial' => $isPartial,
                'is_advance' => false, // Not advance, it's specifically for this month
                'months_covered' => 0,
                'payment_made_this_month' => !$wasPaidEarly,
                'payment_date' => $paymentForThisMonth->created_at
            ];
        }

        // 2. Check if covered by multi-month advance payment
        $advanceStatus = $this->checkIfCoveredByAdvance($month);
        
        if ($advanceStatus['is_covered']) {
            return [
                'status' => 'PAID',
                'amount_paid' => 0, // ZERO - don't count money again
                'is_partial' => false,
                'is_advance' => true,
                'months_covered' => $advanceStatus['months_remaining'],
                'payment_made_this_month' => false,
                'payment_date' => $advanceStatus['original_payment_date']
            ];
        }

        // 3. No payment found
        return [
            'status' => 'UNPAID',
            'amount_paid' => 0,
            'is_partial' => false,
            'is_advance' => false,
            'months_covered' => 0,
            'payment_made_this_month' => false
        ];
    }

    /**
     * Check if this month is covered by a previous multi-month advance payment
     */
    private function checkIfCoveredByAdvance($targetMonth)
    {
        $targetDate = \Carbon\Carbon::createFromFormat('Y-m', $targetMonth)->startOfMonth();
        
        // Get all payments where amount > rent (multi-month payments)
        // that were created BEFORE this target month
        $multiMonthPayments = $this->payments()
            ->where('status', 'paid')
            ->where('amount', '>', $this->rent)
            ->whereDate('created_at', '<', $targetDate)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($multiMonthPayments as $payment) {
            $paymentAmount = $payment->amount;
            $monthsCovered = floor($paymentAmount / $this->rent);
            
            if ($monthsCovered <= 1) continue; // Not a multi-month payment
            
            // Get the month field from the payment to know which month it starts covering
            $paymentMonthField = \Carbon\Carbon::parse($payment->month)->startOfMonth();
            
            // Calculate which months this payment covers
            for ($i = 0; $i < $monthsCovered; $i++) {
                $coveredMonth = $paymentMonthField->copy()->addMonths($i);
                
                if ($coveredMonth->format('Y-m') === $targetMonth) {
                    // This target month is covered by this advance payment
                    $monthsRemaining = $monthsCovered - $i - 1;
                    
                    return [
                        'is_covered' => true,
                        'months_remaining' => $monthsRemaining,
                        'original_payment_date' => $payment->created_at
                    ];
                }
            }
        }

        return ['is_covered' => false];
    }

    /**
     * Get payment for specific month by checking the 'month' field
     * This determines which report the payment should appear in
     */
    public function getPaymentForMonth($month)
    {
        // Parse the target month
        $targetDate = \Carbon\Carbon::createFromFormat('Y-m', $month);
        
        // Look for payments where the 'month' field matches the target month
        $payment = $this->payments()
            ->where('status', 'paid')
            ->whereYear('month', $targetDate->year)
            ->whereMonth('month', $targetDate->month)
            ->first();
        
        return $payment;
    }

    /**
     * Get payments made in specific calendar month (by created_at - when money came in)
     * This is used for "Date of Payment" column
     */
    public function getPaymentsMadeInMonth($month)
    {
        // This method is for showing WHEN the payment was physically received
        // Not which month it's allocated to
        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        
        return $this->payments()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->get();
    }

    /**
     * Get the payment that should be displayed for a month
     * Returns the payment if it's FOR this month (based on 'month' field)
     */
    public function getDisplayPaymentForMonth($month)
    {
        $paymentStatus = $this->getPaymentStatusForReport($month);
        
        if (isset($paymentStatus['payment_date'])) {
            return $this->payments()
                ->where('created_at', $paymentStatus['payment_date'])
                ->first();
        }
        
        return null;
    }
}