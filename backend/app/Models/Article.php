<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    /**
     * status, published_at, editor_id and view_count are controlled by the
     * workflow service and are intentionally not mass assignable.
     */
    protected $fillable = [
        'title', 'slug', 'subtitle', 'summary', 'body',
        'cover_media_id', 'cover_url',
        'author_id', 'primary_category_id', 'city_id', 'region_id',
        'scheduled_at', 'is_featured', 'is_breaking', 'is_sponsored',
        'source_name', 'image_credit',
        'meta_title', 'meta_description', 'canonical_url', 'og_image_path', 'allow_index',
    ];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_breaking' => 'boolean',
            'is_sponsored' => 'boolean',
            'allow_index' => 'boolean',
            'view_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'article_media')
            ->withPivot(['sort_order', 'role'])
            ->orderBy('article_media.sort_order');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->latest('id');
    }

    /** Publicly visible: published status and publication date already reached. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Apply public listing filters (slugs) and optional text search.
     *
     * @param  array{category?: ?string, city?: ?string, region?: ?string, tag?: ?string, q?: ?string, from?: ?string, to?: ?string, featured?: mixed}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['category'] ?? null, function (Builder $q, string $slug) {
                $category = Category::query()->where('slug', $slug)->first();
                $category ? $q->inCategory($category) : $q->whereRaw('1 = 0');
            })
            ->when($filters['city'] ?? null, fn (Builder $q, string $slug) => $q->whereHas('city', fn (Builder $c) => $c->where('slug', $slug)))
            ->when($filters['region'] ?? null, fn (Builder $q, string $slug) => $q->whereHas('region', fn (Builder $r) => $r->where('slug', $slug)))
            ->when($filters['tag'] ?? null, fn (Builder $q, string $slug) => $q->whereHas('tags', fn (Builder $t) => $t->where('tags.slug', $slug)))
            ->when($filters['from'] ?? null, fn (Builder $q, string $date) => $q->where('published_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $q, string $date) => $q->where('published_at', '<=', $date.' 23:59:59'))
            ->when(filter_var($filters['featured'] ?? false, FILTER_VALIDATE_BOOLEAN), fn (Builder $q) => $q->where('is_featured', true))
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $like = '%'.addcslashes($term, '\\%_').'%';
                $q->where(function (Builder $w) use ($like) {
                    $w->where('title', 'like', $like)
                        ->orWhere('subtitle', 'like', $like)
                        ->orWhere('summary', 'like', $like)
                        ->orWhere('body', 'like', $like);
                });
            });
    }

    public function scopeInCategory(Builder $query, Category $category): Builder
    {
        return $query->where(function (Builder $q) use ($category) {
            $q->where('primary_category_id', $category->id)
                ->orWhereHas('categories', fn (Builder $c) => $c->where('categories.id', $category->id));
        });
    }

    /** Resolved cover image URL: uploaded media first, external URL as fallback. */
    public function coverImageUrl(): ?string
    {
        if ($this->cover_media_id && $this->relationLoaded('coverMedia') && $this->coverMedia) {
            return $this->coverMedia->url();
        }

        return $this->cover_url;
    }

    public function isPublic(): bool
    {
        return $this->status === ArticleStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
