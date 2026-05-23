<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactSubmission extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message',
        'ip_address', 'user_agent',
        'replied_at', 'replied_by', 'reply_notes',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function isReplied(): bool
    {
        return $this->replied_at !== null;
    }
}
