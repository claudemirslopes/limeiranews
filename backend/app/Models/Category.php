<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    public const TYPE_GEOGRAPHIC = 'geographic';

    public const TYPE_EDITORIAL = 'editorial';

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'image_path',
        'meta_title', 'meta_description', 'is_active', 'sort_order', 'type',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** Articles whose primary category is this one. */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'primary_category_id');
    }

    /** Articles that list this category as a secondary category. */
    public function secondaryArticles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_category');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
