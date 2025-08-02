<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemImage extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'order_item_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected $appends = ['url']; // viene incluso in JSON automaticamente
    protected $visible = ['id', 'url']; // limita i campi visibili nel JSON se vuoi



    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

}