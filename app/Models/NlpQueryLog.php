<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NlpQueryLog extends Model
{
    protected $fillable = [
        'user_id',
        'intent',
        'operation',
        'raw_text',
        'parsed_json',
        'provider',
        'model',
        'success',
        'error_code',
        'latency_ms',
        'token_usage',
    ];

    protected $casts = [
        'parsed_json' => 'array',
        'token_usage' => 'array',
        'success'     => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
