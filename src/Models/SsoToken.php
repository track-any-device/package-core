<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Database\Factories\SsoTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One-time SSO token issued by login.track-any-device.com to an OAuthClient.
 *
 * The token itself is never stored — only its sha256 hash. Rows are NEVER
 * deleted; they form the audit log of "who logged into which client when".
 * A row with consumed_at = NULL is still claimable; setting consumed_at
 * (inside a DB transaction with lockForUpdate) is what enforces single-use.
 *
 * See TrackAnyDevice\SsoServer\Services\TokenIssuer / TokenVerifier for the issue + consume
 * code paths.
 */
class SsoToken extends Model
{
    /** @use HasFactory<SsoTokenFactory> */
    use HasFactory, UsesCentralConnection;

    protected $table = 'sso_tokens';

    protected $fillable = [
        'token_hash',
        'user_id',
        'oauth_client_id',
        'state',
        'issued_to_ip',
        'consumed_from_ip',
        'issued_at',
        'expires_at',
        'consumed_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oauthClient(): BelongsTo
    {
        return $this->belongsTo(OAuthClient::class);
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
