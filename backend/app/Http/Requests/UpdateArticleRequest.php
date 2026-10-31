<?php

namespace App\Http\Requests;

use App\Models\Article;

class UpdateArticleRequest extends StoreArticleRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article
            && ($this->user()?->can('update', $article) ?? false);
    }

    public function rules(): array
    {
        $rules = parent::rules();

        foreach (['title', 'body', 'primary_category_id'] as $field) {
            array_unshift($rules[$field], 'sometimes');
        }

        return $rules;
    }
}
