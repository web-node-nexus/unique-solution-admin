<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class AppNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'body',
        'image',
        'link_type',
        'link_value',
        'audience',
        'status',
        'sent_by',
        'sent_at',
        'related_type',
        'related_id',
        'fcm_success_count',
        'fcm_failure_count',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'fcm_success_count' => 'integer',
            'fcm_failure_count' => 'integer',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    protected function imageUrl(): CastAttribute
    {
        return CastAttribute::get(function (): ?string {
            if (! $this->image) {
                return null;
            }

            if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
                return $this->image;
            }

            return Storage::disk('public')->url($this->image);
        });
    }
}
