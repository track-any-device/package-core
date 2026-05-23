<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Page extends Model
{
    use UsesCentralConnection;

    protected $fillable = ['title', 'slug', 'meta_title', 'meta_description', 'is_active', 'metadata'];

    protected $casts = ['is_active' => 'boolean', 'metadata' => 'array'];

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

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }
}
