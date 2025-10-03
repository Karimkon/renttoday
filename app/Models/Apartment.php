<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    protected $fillable = ['number','rooms','rent','tenant_id'];

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }

    // Link to payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
