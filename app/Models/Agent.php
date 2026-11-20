<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Agent extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'slug', 'is_active', 'abilities'];

    protected $casts = ['is_active' => 'boolean', 'abilities' => 'array'];
}
