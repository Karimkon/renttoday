<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'payment_id', 'tenant_id', 'apartment_id', 'amount', 'status', 'pdf_path'
    ];

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }

    public function apartment() {
        return $this->belongsTo(Apartment::class);
    }

    public function payment() {
        return $this->belongsTo(Payment::class);
    }
}
