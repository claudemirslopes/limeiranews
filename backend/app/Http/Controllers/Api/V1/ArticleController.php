<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleListResource;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    private const LIST_RELATIONS = [
        'primaryCategory:id,name,slug',
        'city:id,name,slug',
        'region:id,name,slug',
        'author:id,name,public_name,slug',
        'coverMedia',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:120'],
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $articles = Article::query()
            ->published()
            ->filter($filters)
            ->with(self::LIST_RELATIONS)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString();

        return ArticleListResource::collection($articles);
    }

    public function show(string $slug): ArticleResource
    {
        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->with([...self::LIST_RELATIONS, 'categories:id,name,slug', 'tags:id,name,slug', 'author:id,name,public_name,slug,bio,specialty'])
            ->firstOrFail();

        // Internal metric only; never exposed on public payloads.
        DB::table('articles')->where('id', $article->id)->increment('view_count');

        $related = Article::query()
            ->published()
            ->where('primary_category_id', $article->primary_category_id)
            ->whereKeyNot($article->id)
            ->with(self::LIST_RELATIONS)
            ->latest('published_at')
            ->limit(4)
            ->get();

        return (new ArticleResource($article))->additional([
            'related' => ArticleListResource::collection($related)->resolve(),
        ]);
    }
}
