<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Solution extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'title', 'slug', 'description', 'icon_name',
        'gradient_from', 'gradient_to', 'image_path',
        'cta_label', 'cta_href',
        'sort_order', 'is_active', 'is_featured', 'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_active', true)->where('is_featured', true)->orderBy('sort_order');
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
