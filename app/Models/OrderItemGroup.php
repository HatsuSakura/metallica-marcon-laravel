<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItemGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'cer_code_id',
        'label',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function cerCode()
    {
        return $this->belongsTo(CerCode::class, 'cer_code_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_item_group_id');
    }
}
