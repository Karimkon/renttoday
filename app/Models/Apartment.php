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
     * SIMPLE: Get payment status for report
     */
    public function getPaymentStatusForReport($month)
    {
        if (!$this->tenant) {
            return [
                'status' => 'VACANT',
                'amount_paid' => 0,
                'is_partial' => false,
                'is_advance' => false,
                'months_covered' => 0
            ];
        }

        // Get payment for this specific month
        $payment = $this->getPaymentForMonth($month);
        
        if ($payment) {
            $isPartial = $payment->amount < $this->rent;
            return [
                'status' => 'PAID',
                'amount_paid' => $payment->amount,
                'is_partial' => $isPartial,
                'is_advance' => false,
                'months_covered' => 1
            ];
        }

        // Check for advance payments made in this calendar month
        $advancePayments = $this->getPaymentsMadeInMonth($month);
        $totalAdvance = $advancePayments->sum('amount');
        
        if ($totalAdvance > 0) {
            $monthsCovered = floor($totalAdvance / $this->rent);
            $remainder = $totalAdvance % $this->rent;
            
            if ($monthsCovered > 0) {
                return [
                    'status' => 'PAID',
                    'amount_paid' => $this->rent, // Show full rent for advance months
                    'is_partial' => false,
                    'is_advance' => true,
                    'months_covered' => $monthsCovered
                ];
            } elseif ($remainder > 0) {
                return [
                    'status' => 'PAID', 
                    'amount_paid' => $remainder,
                    'is_partial' => true,
                    'is_advance' => false,
                    'months_covered' => 0
                ];
            }
        }

        return [
            'status' => 'UNPAID',
            'amount_paid' => 0,
            'is_partial' => false,
            'is_advance' => false,
            'months_covered' => 0
        ];
    }

    /**
     * Get payment for specific month
     */
    public function getPaymentForMonth($month)
    {
        $formats = ['Y-m', 'F Y', 'M Y'];
        
        foreach ($formats as $format) {
            $monthString = \Carbon\Carbon::createFromFormat('Y-m', $month)->format($format);
            
            $payment = $this->payments()
                ->where(function($query) use ($monthString) {
                    $query->where('month', 'like', "%{$monthString}%")
                          ->orWhere('month', $monthString);
                })
                ->where('status', 'paid')
                ->first();
                
            if ($payment) {
                return $payment;
            }
        }
        
        return null;
    }

    /**
     * Get payments made in specific calendar month
     */
    public function getPaymentsMadeInMonth($month)
    {
        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        
        return $this->payments()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->get();
    }
}