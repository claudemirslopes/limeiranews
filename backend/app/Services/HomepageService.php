<?php

namespace App\Services;

use App\Models\Article;
use App\Models\HomepageSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class HomepageService
{
    /**
     * Visible sections with their articles loaded in the `articles` relation.
     *
     * @return Collection<int, HomepageSection>
     */
    public function sections(): Collection
    {
        $sections = HomepageSection::query()
            ->visible()
            ->with(['category:id,name,slug', 'region:id,name,slug'])
            ->orderBy('sort_order')
            ->get();

        /** @var list<int> $shown ids already displayed in hero/featured blocks */
        $shown = [];

        foreach ($sections as $section) {
            $articles = $this->articlesFor($section, $shown);
            $section->setRelation('articles', $articles);

            if (in_array($section->section_type, [HomepageSection::TYPE_HERO, HomepageSection::TYPE_FEATURED], true)) {
                $shown = array_merge($shown, $articles->pluck('id')->all());
            }
        }

        return $sections;
    }

    /**
     * @param  list<int>  $exclude
     * @return Collection<int, Article>
     */
    public function articlesFor(HomepageSection $section, array $exclude = []): Collection
    {
        $query = Article::query()
            ->published()
            ->with(['primaryCategory:id,name,slug', 'city:id,name,slug', 'region:id,name,slug', 'author:id,name,public_name,slug', 'coverMedia'])
            ->latest('published_at');

        match ($section->section_type) {
            HomepageSection::TYPE_HERO, HomepageSection::TYPE_FEATURED => $query->where('is_featured', true),
            HomepageSection::TYPE_CATEGORY => $this->forCategory($query, $section),
            HomepageSection::TYPE_REGION => $query->where('region_id', $section->region_id),
            HomepageSection::TYPE_MOST_READ => $query->reorder()->orderByDesc('view_count')->orderByDesc('published_at'),
            default => $this->excluding($query, $exclude),
        };

        if ($section->category_id && $section->section_type !== HomepageSection::TYPE_CATEGORY) {
            $this->forCategory($query, $section);
        }

        $articles = (clone $query)->limit(max(1, $section->limit))->get();

        // Hero without any flagged article falls back to the most recent news.
        if ($articles->isEmpty() && $section->section_type === HomepageSection::TYPE_HERO) {
            $articles = Article::query()
                ->published()
                ->with(['primaryCategory:id,name,slug', 'city:id,name,slug', 'region:id,name,slug', 'author:id,name,public_name,slug', 'coverMedia'])
                ->latest('published_at')
                ->limit(max(1, $section->limit))
                ->get();
        }

        return $articles;
    }

    private function forCategory(Builder $query, HomepageSection $section): void
    {
        if ($section->category) {
            $query->inCategory($section->category);
        }
    }

    /**
     * @param  list<int>  $ids
     */
    private function excluding(Builder $query, array $ids): void
    {
        if ($ids !== []) {
            $query->whereNotIn('id', $ids);
        }
    }
}
