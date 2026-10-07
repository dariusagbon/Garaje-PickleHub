<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A short notice from the admin, shown on the home page and the player
 * dashboard until it expires 24 hours after it was posted.
 */
class Announcement extends Model
{
    public const LIFETIME_HOURS = 24;

    protected $fillable = ['title', 'body', 'user_id', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Announcement $announcement) {
            $announcement->expires_at ??= now()->addHours(self::LIFETIME_HOURS);
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Announcements still within their 24 hours. Expired ones simply stop matching. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }
}
