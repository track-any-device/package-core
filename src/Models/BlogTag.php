<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTag extends Model
{
    use UsesCentralConnection;

    protected $fillable = ['name', 'slug', 'color'];

    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class);
    }
}
