<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderHolder extends Model
{
    use HasFactory, SoftDeletes, VersionableTrait;

    protected $keepOldVersions = true; // Keep all versions of the model

    protected $fillable = [
        'order_id',
        'holder_id',
        'holder_piene',
        'holder_vuote',
        'holder_totale'
    ];


    // Relationship to order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function holder()
    {
        return $this->belongsTo(Holder::class, 'holder_id');
    }
}
