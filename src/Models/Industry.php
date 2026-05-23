<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Industry extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'title', 'slug', 'description', 'hero_image', 'icon_name', 'color',
        'sort_order', 'is_active', 'is_featured', 'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->active()->where('is_featured', true);
    }

    public function sections(): MorphMany
    {
        return $this->morphMany(PageSection::class, 'sectionable')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function allSections(): MorphMany
    {
        return $this->morphMany(PageSection::class, 'sectionable')->orderBy('sort_order');
    }
}
