<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;

class IncomingSms extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'raw_message',
        'sender_number',
        'received_at',
        'source',
        'processed_at',
        'processing_error',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }
}
