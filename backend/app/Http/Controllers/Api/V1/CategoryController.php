<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleListResource;
use App\Http\Resources\CategoryResource;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['type' => ['nullable', 'in:geographic,editorial']]);

        $categories = Category::query()
            ->active()
            ->whereNull('parent_id')
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Request $request, string $slug): CategoryResource
    {
        $category = Category::query()
            ->active()
            ->where('slug', $slug)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();

        $articles = Article::query()
            ->published()
            ->inCategory($category)
            ->with(['primaryCategory:id,name,slug', 'city:id,name,slug', 'region:id,name,slug', 'author:id,name,public_name,slug', 'coverMedia'])
            ->orderByDesc('published_at')
            ->paginate(min((int) $request->query('per_page', 12), 50))
            ->withQueryString();

        return (new CategoryResource($category))->additional([
            'articles' => ArticleListResource::collection($articles)->response($request)->getData(true),
        ]);
    }
}
