<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessType extends Model
{
    protected $fillable = ['name', 'description'];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'business_type_id');
    }
}
