<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;

/** @mixin Article */
class ArticleResource extends ArticleListResource
{
    public function toArray(Request $request): array
    {
        $admin = $request->is('api/v1/admin/*');

        return array_merge(parent::toArray($request), [
            'body' => $this->body,
            'source_name' => $this->source_name,
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])->values()),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
            ])->values()),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'name' => $this->author->public_name ?? $this->author->name,
                'slug' => $this->author->slug,
                'bio' => $this->author->bio,
                'specialty' => $this->author->specialty,
            ]),
            'seo' => [
                'meta_title' => $this->meta_title ?? $this->title,
                'meta_description' => $this->meta_description ?? $this->summary,
                'canonical_url' => $this->canonical_url,
                'og_image' => $this->og_image_path ? asset('storage/'.$this->og_image_path) : $this->coverImageUrl(),
                'allow_index' => $this->allow_index,
            ],
            'cover_media_id' => $this->when($admin, $this->cover_media_id),
            'revisions' => $this->when($admin && $this->relationLoaded('revisions'), fn () => $this->revisions->map(fn ($r) => [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'title' => $r->title,
                'status' => $r->status,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values()),
        ]);
    }
}
