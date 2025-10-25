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
        'location', // Add location field (Mukono, Bweyogerere, etc.)
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

    // Add landlord relationship and location
    public function scopeByLandlord($query, $landlordId)
    {
        return $query->where('landlord_id', $landlordId);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function getCurrentMonthPayment()
    {
        $currentMonth = now()->format('Y-m');
        return $this->payments()
            ->where('month', 'like', $currentMonth . '%')
            ->where('status', 'paid')
            ->first();
    }

    public function getPaymentStatusForMonth($month)
    {
        $payment = $this->payments()
            ->where('month', 'like', $month . '%')
            ->where('status', 'paid')
            ->first();

        return $payment ? 'paid' : 'unpaid';
    }
}