<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'volume',
        'is_custom',
        'equivalent_holder_id',
        'equivalent_units',
    ];

    public function equivalentHolder()
    {
        return $this->belongsTo(self::class, 'equivalent_holder_id');
    }
}
