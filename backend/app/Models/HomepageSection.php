<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageSection extends Model
{
    public const TYPE_HERO = 'hero';

    public const TYPE_LATEST = 'latest';

    public const TYPE_CATEGORY = 'category';

    public const TYPE_REGION = 'region';

    public const TYPE_FEATURED = 'featured';

    public const TYPE_MOST_READ = 'most_read';

    protected $fillable = [
        'key', 'title', 'section_type', 'category_id', 'region_id',
        'limit', 'sort_order', 'is_visible', 'config',
    ];

    protected function casts(): array
    {
        return [
            'limit' => 'integer',
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
            'config' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
