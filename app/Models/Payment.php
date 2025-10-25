<?php
// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'processed_by',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'includes_gym' => 'boolean',
        'paid_at' => 'datetime',
        'month' => 'date'
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
}