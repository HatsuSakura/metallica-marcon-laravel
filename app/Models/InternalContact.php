<?php

namespace App\Models;

use App\Enums\InternalContactRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InternalContact extends Model
{
    use HasFactory;


    /*
    It ensures that whenever you access the role field, it will return an instance of the SiteTipologia enum rather than just a string (e.g., 'worker').
    if ($user->role === UserRole::WORKER)
    */
    protected $casts = [
        'role' => InternalContactRole::class,
    ];

    public function site(): BelongsTo{
        return $this->belongsTo(Site::class, 'site_id');
    }



}
