<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandlordExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'landlord_id',
        'apartment_id',
        'month',
        'description',
        'amount',
        'category',
        'expense_date',
        'reference',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date'
    ];

    // Expense categories
    const CATEGORY_MAINTENANCE = 'maintenance';
    const CATEGORY_REPAIRS = 'repairs';
    const CATEGORY_PAINTING = 'painting';
    const CATEGORY_UTILITIES = 'utilities';
    const CATEGORY_TAXES = 'taxes';
    const CATEGORY_INSURANCE = 'insurance';
    const CATEGORY_OTHER = 'other';

    public static function getCategories()
    {
        return [
            self::CATEGORY_MAINTENANCE => 'Maintenance',
            self::CATEGORY_REPAIRS => 'Repairs',
            self::CATEGORY_PAINTING => 'Painting',
            self::CATEGORY_UTILITIES => 'Utilities',
            self::CATEGORY_TAXES => 'Taxes',
            self::CATEGORY_INSURANCE => 'Insurance',
            self::CATEGORY_OTHER => 'Other'
        ];
    }

    public function getCategoryLabelAttribute()
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }

    /**
     * Relationships
     */
    public function landlord()
    {
        return $this->belongsTo(Landlord::class);
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeForMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    public function scopeForLandlord($query, $landlordId)
    {
        return $query->where('landlord_id', $landlordId);
    }

    /**
     * Get total expenses for a landlord in a specific month
     */
    public static function getTotalForMonth($landlordId, $month)
    {
        return self::where('landlord_id', $landlordId)
            ->where('month', $month)
            ->sum('amount');
    }
}
