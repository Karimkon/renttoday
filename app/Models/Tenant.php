<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['name','email','phone', 'biling_day', 'credit_balance'];

    public function apartment() {
        return $this->hasOne(Apartment::class);
    }

    public function payments() {
        return $this->hasMany(Payment::class);
    }
}
