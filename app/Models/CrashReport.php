<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrashReport extends Model
{
    protected $fillable = [
        'user_id',
        'level',
        'message',
        'stack',
        'screen',
        'context',
        'platform',
        'app_version',
        'device_id',
        'is_fatal',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'is_fatal' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
