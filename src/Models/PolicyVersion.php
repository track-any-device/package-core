<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/*
 * COMPLIANCE REQUIREMENT — READ BEFORE EVERY COMMIT
 * ─────────────────────────────────────────────────
 * After ANY change to: data collection, authentication flows, cookie usage,
 * third-party integrations, user data handling, or stored data structures —
 * you MUST:
 *
 *   1. Create a new database/seeders/PolicyVersionSeeder_{YYYY_MM_DD}.php
 *      seeder (or a new numbered seeder) that:
 *      a) Sets is_current = false on the previous version of the affected
 *         policy type(s).
 *      b) Inserts a new row with an incremented version, the updated
 *         content, and a new effective_from date.
 *
 *   2. Run: php artisan db:seed --class=PolicyVersionSeeder_YYYY_MM_DD
 *
 *   3. Old versions must NEVER be deleted — they must remain accessible via
 *      the versioned routes /terms/{version}, /privacy/{version}, etc.
 *
 * Failure to do this means the platform is non-compliant.
 */

/**
 * Versioned policy document: terms of service, privacy policy, or cookie policy.
 *
 * Lives in the central database. Exactly one row per type may carry
 * is_current = true at any time; setCurrent() unsets the previous current
 * row in the same atomic transaction.
 *
 * @property int $id
 * @property string $type
 * @property string $version
 * @property string $title
 * @property string $content
 * @property Carbon $effective_from
 * @property bool $is_current
 */
class PolicyVersion extends Model
{
    use UsesCentralConnection;

    public const TYPE_TERMS = 'terms';

    public const TYPE_PRIVACY = 'privacy';

    public const TYPE_COOKIE = 'cookie';

    public const TYPES = [self::TYPE_TERMS, self::TYPE_PRIVACY, self::TYPE_COOKIE];

    protected $fillable = [
        'type',
        'version',
        'title',
        'content',
        'effective_from',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeHistory(Builder $query, string $type): Builder
    {
        return $query->ofType($type)->orderByDesc('effective_from');
    }

    public static function currentOf(string $type): ?self
    {
        return static::query()->ofType($type)->current()->first();
    }

    /**
     * Mark this row as the current version of its type, unsetting any
     * previous current row in a single transaction.
     */
    public function setCurrent(): void
    {
        DB::connection($this->getConnectionName())->transaction(function () {
            static::query()
                ->ofType($this->type)
                ->where('id', '!=', $this->id)
                ->update(['is_current' => false]);

            $this->forceFill(['is_current' => true])->save();
        });
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_TERMS => 'Terms of Service',
            self::TYPE_PRIVACY => 'Privacy Policy',
            self::TYPE_COOKIE => 'Cookie Policy',
            default => ucfirst($this->type).' Policy',
        };
    }
}
