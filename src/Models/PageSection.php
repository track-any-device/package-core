<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PageSection extends Model
{
    use UsesCentralConnection;

    protected $fillable = ['sectionable_type', 'sectionable_id', 'type', 'identifier', 'content', 'sort_order', 'is_active'];

    protected $casts = ['content' => 'array', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    public function sectionable(): MorphTo
    {
        return $this->morphTo();
    }
}
