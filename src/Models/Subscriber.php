<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'email',
        'name',
        'source',
        'ip_address',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }
}
