<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Blog extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'cover_image',
        'author_id', 'published_at',
        'is_active', 'is_featured', 'sort_order', 'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
        'sort_order' => 'integer',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('published_at')->where('published_at', '<=', now())->orderByDesc('published_at');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->published()->where('is_featured', true)->orderBy('sort_order');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class);
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

    public static function findPublishedBySlug(string $slug): ?self
    {
        return static::published()->where('slug', $slug)->first();
    }
}
