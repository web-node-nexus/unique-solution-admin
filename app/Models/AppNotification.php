<?php

namespace App\Models;

use App\Support\PublishingWindow;
use Illuminate\Database\Eloquent\Builder;
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
        'is_active',
        'starts_at',
        'ends_at',
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
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sent_at' => 'datetime',
            'fcm_success_count' => 'integer',
            'fcm_failure_count' => 'integer',
        ];
    }

    public function scopeVisibleOnApp(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where('status', 'sent')
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function isCurrentlyLive(): bool
    {
        return $this->status === 'sent'
            && PublishingWindow::isVisibleOnApp((bool) $this->is_active, $this->starts_at, $this->ends_at);
    }

    public function scheduleState(): string
    {
        return PublishingWindow::state((bool) $this->is_active, $this->starts_at, $this->ends_at);
    }

    public function isDueToSend(): bool
    {
        if (! $this->is_active || $this->status === 'sent') {
            return false;
        }

        return PublishingWindow::isVisibleOnApp(true, $this->starts_at, $this->ends_at);
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
