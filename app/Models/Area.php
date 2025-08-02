<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;


    public function sites()
    {
        return $this->belongsToMany(Site::class)
                    ->withPivot('is_preferred')
                    ->withTimestamps();
    }



}
