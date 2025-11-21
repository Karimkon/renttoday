<?php
// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'apartment_id', 
        'month',
        'amount',
        'payment_method',
        'reference_number',
        'includes_gym',
        'status',
        'order_tracking_id',
        'paid_at',
        'actual_payment_date',
        'processed_by',
        'notes',
        'is_advance_payment',
        'allocated_months',
        'original_amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'actual_payment_date' => 'date',
        'month' => 'date',
        'is_advance_payment' => 'boolean',
        'allocated_months' => 'array',
        'original_amount' => 'decimal:2'
    ];

    // Payment methods
    const METHOD_CASH = 'cash';
    const METHOD_PESAPAL = 'pesapal';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_MOBILE_MONEY = 'mobile_money';

    // Payment statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    public static function getPaymentMethods()
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_PESAPAL => 'Pesapal',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_MOBILE_MONEY => 'Mobile Money'
        ];
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_REFUNDED => 'Refunded'
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function landlord()
    {
        return $this->hasOneThrough(Landlord::class, Apartment::class, 'id', 'id', 'apartment_id', 'landlord_id');
    }

    public function getPaymentMethodLabelAttribute()
    {
        return self::getPaymentMethods()[$this->payment_method] ?? $this->payment_method;
    }

    public function getStatusLabelAttribute()
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Get payments allocated to a specific month
     */
     public function scopeForMonth($query, $month)
    {
        $monthFormatted = Carbon::createFromFormat('Y-m', $month)->format('Y-m');
        
        return $query->where(function($q) use ($monthFormatted) {
            // Payment's month field matches
            $q->whereRaw("DATE_FORMAT(month, '%Y-%m') = ?", [$monthFormatted])
              // OR payment's allocated_months contains this month
              ->orWhereJsonContains('allocated_months', $monthFormatted);
        });
    }

    /**
     * Check if this payment covers a specific month
     */
     public function coversMonth($month)
    {
        $monthFormatted = Carbon::createFromFormat('Y-m', $month)->format('Y-m');
        
        // Direct match with payment's month field
        if ($this->month->format('Y-m') === $monthFormatted) {
            return true;
        }
        
        // Check allocated months for advance payments
        if ($this->is_advance_payment && $this->allocated_months) {
            return in_array($monthFormatted, $this->allocated_months);
        }
        
        return false;
    }

    /**
     * Get the amount allocated to a specific month
     */
     public function getAmountForMonth($month)
    {
        if (!$this->coversMonth($month)) {
            return 0;
        }
        
        // Always return the actual payment amount for this month
        return $this->amount;
    }

}