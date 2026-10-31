<?php

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Article::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'cover_url' => ['nullable', 'url', 'max:1000'],
            'primary_category_id' => ['required', 'integer', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:60'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'scheduled_at' => ['nullable', 'date'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_breaking' => ['sometimes', 'boolean'],
            'is_sponsored' => ['sometimes', 'boolean'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'image_credit' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url', 'max:1000'],
            'og_image_path' => ['nullable', 'string', 'max:255'],
            'allow_index' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'body' => 'texto',
            'primary_category_id' => 'categoria principal',
            'summary' => 'resumo',
        ];
    }
}
