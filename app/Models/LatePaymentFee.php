<?php
// app/Models/LatePaymentFee.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LatePaymentFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'apartment_id',
        'tenant_id',
        'month',
        'amount',
        'original_rent',
        'due_date',
        'status',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'original_rent' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime'
    ];

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeForMonth($query, $month)
    {
        return $query->where('month', $month);
    }
}