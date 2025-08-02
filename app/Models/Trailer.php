<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trailer extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'plate',
        'is_front_cargo',
        'load_capacity',
    ];

    /**
     * Get the vehicles that prefer this trailer.
     */
    public function usualDriver()
    {
        return $this->hasOne(Vehicle::class, 'trailer_id');
    }

}