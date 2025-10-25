<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'amount',
        'type',
        'date',
        'category',
        'payment_method',
        'reference',
        'status',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date'
    ];

    // Expense types
    const TYPE_OPERATING = 'operating';
    const TYPE_ADMINISTRATIVE = 'administrative';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_OTHER = 'other';

    // Expense categories
    const CATEGORY_UTILITIES = 'utilities';
    const CATEGORY_SALARIES = 'salaries';
    const CATEGORY_OFFICE_SUPPLIES = 'office_supplies';
    const CATEGORY_MAINTENANCE = 'maintenance';
    const CATEGORY_INSURANCE = 'insurance';
    const CATEGORY_TAXES = 'taxes';
    const CATEGORY_OTHER = 'other';

    public static function getTypes()
    {
        return [
            self::TYPE_OPERATING => 'Operating Expenses',
            self::TYPE_ADMINISTRATIVE => 'Administrative Expenses',
            self::TYPE_MAINTENANCE => 'Maintenance Expenses',
            self::TYPE_OTHER => 'Other Expenses'
        ];
    }

    public static function getCategories()
    {
        return [
            self::CATEGORY_UTILITIES => 'Utilities',
            self::CATEGORY_SALARIES => 'Salaries',
            self::CATEGORY_OFFICE_SUPPLIES => 'Office Supplies',
            self::CATEGORY_MAINTENANCE => 'Maintenance',
            self::CATEGORY_INSURANCE => 'Insurance',
            self::CATEGORY_TAXES => 'Taxes',
            self::CATEGORY_OTHER => 'Other'
        ];
    }

    public function getTypeLabelAttribute()
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    public function getCategoryLabelAttribute()
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }
}