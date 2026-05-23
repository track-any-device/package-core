<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;

class NavLink extends Model
{
    use UsesCentralConnection;

    public const PLACEMENT_HEADER = 'header';

    public const PLACEMENT_FOOTER_QUICK = 'footer_quick';

    public const PLACEMENT_FOOTER_SUPPORT = 'footer_support';

    public const PLACEMENT_FOOTER_LEGAL = 'footer_legal';

    public const PLACEMENTS = [
        self::PLACEMENT_HEADER,
        self::PLACEMENT_FOOTER_QUICK,
        self::PLACEMENT_FOOTER_SUPPORT,
        self::PLACEMENT_FOOTER_LEGAL,
    ];

    protected $fillable = ['label', 'href', 'target', 'placement', 'parent_id', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopePlacement($query, string $placement)
    {
        return $query->where('placement', $placement);
    }
}
