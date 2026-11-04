<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class JourneyBaseline extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['data' => 'array', 'captured_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('La baseline è immutabile.'));
        static::deleting(fn () => throw new LogicException('La baseline non può essere eliminata.'));
    }
}
