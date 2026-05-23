<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    use UsesCentralConnection;

    public const TYPE_PHONE_VERIFICATION = 'phone_verification';

    public const TYPE_SMS_CHALLENGE = 'sms_challenge';

    public $timestamps = false;

    protected $fillable = ['user_id', 'type', 'phone', 'code', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeForUser(Builder $query, int $userId, string $type): Builder
    {
        return $query->where('user_id', $userId)->where('type', $type);
    }

    /** Replace any existing OTP of this type for the user with a fresh one. */
    public static function issue(int $userId, string $type, string $phone, int $ttlMinutes = 5): self
    {
        self::where('user_id', $userId)->where('type', $type)->delete();

        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'phone' => $phone,
            'code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    /** Find a valid (non-expired) OTP for a user + type. Returns null if absent or expired. */
    public static function findValid(int $userId, string $type): ?self
    {
        return self::where('user_id', $userId)
            ->where('type', $type)
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();
    }
}
