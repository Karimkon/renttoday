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
        'notes',
        'payment_status'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'payment_status' => 'array'
    ];

    public function apartments()
    {
        return $this->hasMany(Apartment::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Apartment::class);
    }

    /**
     * Get all expenses for this landlord
     */
    public function expenses()
    {
        return $this->hasMany(LandlordExpense::class);
    }

    /**
     * Get expenses for a specific month
     */
    public function getExpensesForMonth($month)
    {
        return $this->expenses()->where('month', $month)->get();
    }

    /**
     * Get total expenses for a specific month
     */
    public function getTotalExpensesForMonth($month)
    {
        return $this->expenses()->where('month', $month)->sum('amount');
    }

    /**
     * Get all report notes for this landlord.
     */
    public function reportNotes()
    {
        return $this->hasMany(ReportNote::class);
    }

    /**
     * Get the report note for a specific month.
     */
    public function getNoteForMonth($month)
    {
        return $this->reportNotes()->where('month', $month)->first();
    }

    /**
     * Create or update a note for a specific month.
     */
    public function saveNoteForMonth($month, $notes, $userId)
    {
        return $this->reportNotes()->updateOrCreate(
            ['month' => $month],
            [
                'notes' => $notes,
                'created_by' => $userId
            ]
        );
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

    /**
     * Mark payment as paid for a specific month
     */
    public function markPaymentAsPaid($month, $amount)
    {
        $status = $this->payment_status ?? [];
        $status[$month] = [
            'paid' => true,
            'paid_at' => now()->toDateTimeString(),
            'amount' => $amount,
            'paid_by' => auth()->id()
        ];
        
        $this->update(['payment_status' => $status]);
    }

    /**
     * Mark payment as unpaid for a specific month
     */
    public function markPaymentAsUnpaid($month)
    {
        $status = $this->payment_status ?? [];
        if (isset($status[$month])) {
            unset($status[$month]);
            $this->update(['payment_status' => $status]);
        }
    }

    /**
     * Check if payment is paid for a specific month
     */
    public function isPaymentPaid($month)
    {
        $status = $this->payment_status ?? [];
        return isset($status[$month]) && $status[$month]['paid'] === true;
    }

    /**
     * Get payment status for a specific month
     */
    public function getPaymentStatus($month)
    {
        $status = $this->payment_status ?? [];
        return $status[$month] ?? ['paid' => false, 'paid_at' => null, 'amount' => 0];
    }
}