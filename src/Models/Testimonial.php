<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name', 'role', 'company', 'quote',
        'avatar_path', 'avatar_initials', 'avatar_color',
        'rating', 'campaign',
        'is_featured', 'is_approved', 'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'rating' => 'integer',
        'sort_order' => 'integer',
    ];

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_approved', true)->orderBy('sort_order');
    }
}
