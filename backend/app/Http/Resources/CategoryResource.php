<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'meta_title' => $this->meta_title ?? $this->name,
            'meta_description' => $this->meta_description,
            'type' => $this->type,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'articles' => ArticleListResource::collection($this->whenLoaded('articles')),
        ];
    }
}
