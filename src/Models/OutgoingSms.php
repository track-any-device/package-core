<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An outgoing SMS request. Enqueued by any central app (login/admin/app); delivered by `app`'s
 * worker via the SMS gateway. Counterpart to {@see IncomingSms}.
 */
class OutgoingSms extends Model
{
    use UsesCentralConnection;

    protected $table = 'outgoing_sms';

    protected $fillable = [
        'device_id',
        'recipient',
        'message',
        'source',
        'status',
        'attempts',
        'sent_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function markSent(): void
    {
        $this->update(['status' => 'sent', 'sent_at' => now(), 'attempts' => $this->attempts + 1]);
    }

    public function markFailed(string $reason): void
    {
        $this->update(['status' => 'failed', 'error' => $reason, 'attempts' => $this->attempts + 1]);
    }
}
