<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\ProductType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Product extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name',
        'sku',
        'product_type',
        'productable_type',
        'productable_id',
        'price',
        'currency',
        'stock',
        'is_active',
        'description',
        'image',
        'images',
        'meta',
    ];

    protected $casts = [
        'product_type' => ProductType::class,
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'images' => 'array',
        'meta' => 'array',
    ];

    public function productable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Convert this product's base price into a target country's currency,
     * applying that country's `conversion_rate` plus markup.
     *
     * The product's `currency` is assumed to be the default country's
     * currency. Returns `null` if no country is supplied.
     */
    public function priceFor(?Country $country): ?float
    {
        if ($country === null) {
            return (float) $this->price;
        }

        $base = (float) $this->price;

        return round($base * $country->effectiveRate(), (int) $country->decimal_values);
    }

    public function formattedPriceFor(?Country $country): ?string
    {
        $price = $this->priceFor($country);
        if ($price === null) {
            return null;
        }

        return $country?->formatAmount($price) ?? number_format($price, 2);
    }
}
