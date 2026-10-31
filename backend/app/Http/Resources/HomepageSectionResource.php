<?php

namespace App\Http\Resources;

use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HomepageSection */
class HomepageSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title' => $this->title,
            'section_type' => $this->section_type,
            'category_id' => $this->category_id,
            'region_id' => $this->region_id,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'region' => $this->whenLoaded('region', fn () => $this->region ? [
                'name' => $this->region->name,
                'slug' => $this->region->slug,
            ] : null),
            'limit' => $this->limit,
            'sort_order' => $this->sort_order,
            'is_visible' => $this->is_visible,
            'config' => $this->config,
            'articles' => ArticleListResource::collection($this->whenLoaded('articles')),
        ];
    }
}
