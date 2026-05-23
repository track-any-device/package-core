<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name',
        'iso_code',
        'country_code',
        'currency',
        'currency_code',
        'code_prepend_or_postpend',
        'thousands_separator',
        'hundreds_separator',
        'decimal_values',
        'decimal_separator',
        'is_default',
        'is_fallback',
        'timezone',
        'sms_gateway',
        'conversion_rate',
        'conversion_markup_percent',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_fallback' => 'boolean',
        'is_active' => 'boolean',
        'decimal_values' => 'integer',
        'conversion_rate' => 'decimal:6',
        'conversion_markup_percent' => 'decimal:2',
    ];

    public function gsmNetworks(): HasMany
    {
        return $this->hasMany(GsmNetwork::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'mobile_country_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeWithSmsGateway(Builder $q): Builder
    {
        return $q->whereNotNull('sms_gateway');
    }

    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first();
    }

    public static function fallback(): ?self
    {
        return static::query()->where('is_fallback', true)->first();
    }

    /**
     * Format an amount for this country.
     *
     * Honours decimal_values, separators, currency code, and prepend/postpend.
     */
    public function formatAmount(float $amount): string
    {
        $decimals = (int) $this->decimal_values;
        $rounded = number_format(
            $amount,
            $decimals,
            $this->decimal_separator,
            $this->thousands_separator,
        );

        return $this->code_prepend_or_postpend === 'prepend'
            ? "{$this->currency_code} {$rounded}"
            : "{$rounded} {$this->currency_code}";
    }

    /**
     * Effective rate used when converting from the default country's currency.
     * Includes the markup percentage so callers don't recompute it.
     */
    public function effectiveRate(): float
    {
        return (float) $this->conversion_rate * (1 + ((float) $this->conversion_markup_percent / 100));
    }
}
