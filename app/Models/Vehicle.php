<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'plate',
        'type',
        'has_trailer',
        'load_capacity',
        'driver_id',
        'trailer_id',
    ];

    public function usualDriver()
    {
        return $this->belongsTo(User::class, 'driver_id')->where('role', 'driver');
    }

    public function preferredTrailer()
    {
        return $this->belongsTo(Trailer::class, 'trailer_id');
    }

}
