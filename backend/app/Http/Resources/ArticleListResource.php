<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Article */
class ArticleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $admin = $request->is('api/v1/admin/*');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'summary' => $this->summary,
            'cover_url' => $this->coverImageUrl(),
            'image_credit' => $this->image_credit,
            'published_at' => $this->published_at?->toIso8601String(),
            'is_featured' => $this->is_featured,
            'is_breaking' => $this->is_breaking,
            'is_sponsored' => $this->is_sponsored,
            'category' => $this->whenLoaded('primaryCategory', fn () => $this->primaryCategory ? [
                'id' => $this->primaryCategory->id,
                'name' => $this->primaryCategory->name,
                'slug' => $this->primaryCategory->slug,
            ] : null),
            'city' => $this->whenLoaded('city', fn () => $this->city ? [
                'id' => $this->city->id,
                'name' => $this->city->name,
                'slug' => $this->city->slug,
            ] : null),
            'region' => $this->whenLoaded('region', fn () => $this->region ? [
                'id' => $this->region->id,
                'name' => $this->region->name,
                'slug' => $this->region->slug,
            ] : null),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'name' => $this->author->public_name ?? $this->author->name,
                'slug' => $this->author->slug,
            ]),
            $this->mergeWhen($admin, fn () => [
                'status' => $this->status->value,
                'status_label' => $this->status->label(),
                'scheduled_at' => $this->scheduled_at?->toIso8601String(),
                'author_id' => $this->author_id,
                'editor_id' => $this->editor_id,
                'view_count' => $this->view_count,
                'updated_at' => $this->updated_at?->toIso8601String(),
                'created_at' => $this->created_at?->toIso8601String(),
            ]),
        ];
    }
}
