<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Landlord extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'commission_rate',
        'address',
        'notes'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2'
    ];

    public function apartments()
    {
        return $this->hasMany(Apartment::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Apartment::class);
    }

    // Calculate total commission for a specific period
public function calculateCommission($startDate, $endDate)
{
    return $this->payments()
        ->whereBetween('month', [$startDate, $endDate])
        ->where('status', 'paid')
        ->sum('amount') * ($this->commission_rate / 100);
}

    // Calculate total rent collected for landlord
    public function totalRentCollected($startDate, $endDate)
    {
        return $this->payments()
            ->whereBetween('month', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('amount');
    }

    // Calculate amount due to landlord (rent - commission)
    public function amountDueToLandlord($startDate, $endDate)
    {
        $totalRent = $this->totalRentCollected($startDate, $endDate);
        $commission = $this->calculateCommission($startDate, $endDate);
        
        return $totalRent - $commission;
    }
}