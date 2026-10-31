<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleListResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'category' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $articles = Article::query()
            ->published()
            ->filter($filters)
            ->with(['primaryCategory:id,name,slug', 'city:id,name,slug', 'region:id,name,slug', 'author:id,name,public_name,slug', 'coverMedia'])
            ->orderByDesc('published_at')
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString();

        return ArticleListResource::collection($articles)->additional(['query' => $filters['q']]);
    }
}
