<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Machine API key that authorises a server-tenant instance (hosted or
 * on-premise) to call the central app/ REST API on behalf of a tenant.
 *
 * The plain key is shown exactly once — on creation — and never stored.
 * Only the bcrypt hash lives in the database. Validation uses Hash::check().
 *
 * One tenant can hold multiple keys to allow rotation without downtime:
 *   1. Generate a new key.
 *   2. Deploy the new key to the server-tenant container.
 *   3. Delete the old key once the new one is confirmed working.
 */
class TenantApiKey extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'tenant_id',
        'name',
        'key_hash',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Key lifecycle ─────────────────────────────────────────────────────────

    /**
     * Generate a new key for the given tenant.
     *
     * Returns both the persisted record and the plain-text key.
     * Store the plain key somewhere safe immediately — it cannot be recovered.
     *
     * @return array{record: static, plain_key: string}
     */
    public static function generate(Tenant $tenant, string $name = 'Default'): array
    {
        $plain = 'tpk_' . Str::random(48);

        $record = static::create([
            'tenant_id' => $tenant->id,
            'name'      => $name,
            'key_hash'  => Hash::make($plain),
        ]);

        return ['record' => $record, 'plain_key' => $plain];
    }

    /**
     * Validate a raw key string against this record's stored hash.
     */
    public function validate(string $rawKey): bool
    {
        return Hash::check($rawKey, $this->key_hash);
    }

    /**
     * Record that this key was used, throttled to once per minute to avoid
     * a write on every single API request.
     */
    public function recordUsage(): void
    {
        if ($this->last_used_at === null || $this->last_used_at->diffInMinutes(now()) >= 1) {
            $this->updateQuietly(['last_used_at' => now()]);
        }
    }

    // ── Lookup helper ─────────────────────────────────────────────────────────

    /**
     * Find and validate a raw bearer token against all keys for a tenant.
     * Returns the matching TenantApiKey or null if no key matches.
     */
    public static function findValid(int $tenantId, string $rawKey): ?static
    {
        return static::where('tenant_id', $tenantId)
            ->get()
            ->first(fn (self $key) => $key->validate($rawKey));
    }
}
