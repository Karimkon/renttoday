<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'landlord_id',
        'month',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'month' => 'string'
    ];

    public function landlord()
    {
        return $this->belongsTo(Landlord::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}