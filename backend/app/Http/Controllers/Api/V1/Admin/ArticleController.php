<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleListResource;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Redirect;
use App\Models\Tag;
use App\Services\ArticleWorkflowService;
use App\Services\SlugService;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    private const RELATIONS = [
        'primaryCategory:id,name,slug', 'city:id,name,slug', 'region:id,name,slug',
        'author:id,name,public_name,slug', 'coverMedia',
    ];

    public function __construct(
        private readonly ArticleWorkflowService $workflow,
        private readonly SlugService $slugs,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Article::class);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(ArticleStatus::values())],
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'author_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();

        $articles = Article::query()
            ->filter($filters)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['author_id'] ?? null, fn ($q, $id) => $q->where('author_id', $id))
            // Writers only see their own articles.
            ->when($user->isWriter() && ! $user->isEditor() && ! $user->isAdmin(), fn ($q) => $q->where('author_id', $user->id))
            ->with(self::RELATIONS)
            ->latest('updated_at')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return ArticleListResource::collection($articles);
    }

    public function show(Article $article): ArticleResource
    {
        $this->authorize('view', $article);

        return new ArticleResource($this->loadFull($article));
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $article = DB::transaction(function () use ($request, $data) {
            $article = new Article($this->attributes($data, $request->user()));
            $article->slug = $this->slugs->unique($data['slug'] ?? $data['title'], Article::class);
            $article->author_id = $request->user()->id;
            $article->status = ArticleStatus::Draft;
            $article->save();

            $this->syncRelations($article, $data);
            $this->workflow->snapshot($article, $request->user());
            AuditLog::record('article.created', $article, [], $request->user(), $request);

            return $article;
        });

        return (new ArticleResource($this->loadFull($article)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateArticleRequest $request, Article $article): ArticleResource
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $article, $data) {
            $oldSlug = $article->slug;

            $article->fill($this->attributes($data, $request->user()));

            if (isset($data['slug']) && $data['slug'] !== $article->slug) {
                $article->slug = $this->slugs->unique($data['slug'], Article::class, $article->id);
            }

            $article->save();

            if ($article->slug !== $oldSlug && $article->status === ArticleStatus::Published) {
                Redirect::query()->updateOrCreate(
                    ['from_path' => '/noticia/'.$oldSlug],
                    ['to_path' => '/noticia/'.$article->slug, 'status_code' => 301],
                );
            }

            $this->syncRelations($article, $data);
            $this->workflow->snapshot($article, $request->user());
            AuditLog::record('article.updated', $article, ['fields' => array_keys($data)], $request->user(), $request);
        });

        return new ArticleResource($this->loadFull($article->refresh()));
    }

    public function destroy(Request $request, Article $article): JsonResponse
    {
        $this->authorize('delete', $article);

        AuditLog::record('article.deleted', $article, [], $request->user(), $request);
        $article->delete();

        return response()->json(['message' => 'Matéria movida para a lixeira.']);
    }

    public function publish(Request $request, Article $article): ArticleResource
    {
        return $this->transition($request, $article, ArticleStatus::Published);
    }

    public function schedule(Request $request, Article $article): ArticleResource
    {
        $data = $request->validate(['scheduled_at' => ['required', 'date', 'after:now']]);

        return $this->transition($request, $article, ArticleStatus::Scheduled, Carbon::parse($data['scheduled_at']));
    }

    public function submitReview(Request $request, Article $article): ArticleResource
    {
        return $this->transition($request, $article, ArticleStatus::InReview);
    }

    public function approve(Request $request, Article $article): ArticleResource
    {
        return $this->transition($request, $article, ArticleStatus::Approved);
    }

    public function requestRevision(Request $request, Article $article): ArticleResource
    {
        return $this->transition($request, $article, ArticleStatus::RevisionRequested);
    }

    public function archive(Request $request, Article $article): ArticleResource
    {
        return $this->transition($request, $article, ArticleStatus::Archived);
    }

    public function duplicate(Request $request, Article $article): JsonResponse
    {
        $this->authorize('duplicate', $article);

        $copy = DB::transaction(function () use ($request, $article) {
            $copy = $article->replicate([
                'status', 'published_at', 'scheduled_at', 'view_count', 'editor_id', 'slug',
            ]);
            $copy->title = $article->title.' (cópia)';
            $copy->slug = $this->slugs->unique($copy->title, Article::class);
            $copy->author_id = $request->user()->id;
            $copy->status = ArticleStatus::Draft;
            $copy->is_featured = false;
            $copy->is_breaking = false;
            $copy->save();

            $copy->categories()->sync($article->categories()->pluck('categories.id'));
            $copy->tags()->sync($article->tags()->pluck('tags.id'));
            $this->workflow->snapshot($copy, $request->user());
            AuditLog::record('article.duplicated', $copy, ['source_id' => $article->id], $request->user(), $request);

            return $copy;
        });

        return (new ArticleResource($this->loadFull($copy)))->response()->setStatusCode(201);
    }

    private function transition(Request $request, Article $article, ArticleStatus $target, ?Carbon $scheduledAt = null): ArticleResource
    {
        $article = $this->workflow->transition($article, $target, $request->user(), $scheduledAt);

        return new ArticleResource($this->loadFull($article));
    }

    /**
     * Mass-assignable attributes with the body sanitized.
     *
     * Highlight flags are editorial decisions and ignored for writers.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, \App\Models\User $user): array
    {
        $attributes = collect($data)->except(['slug', 'category_ids', 'tags'])->all();

        if (! $user->canPublish()) {
            unset($attributes['is_featured'], $attributes['is_breaking']);
        }

        if (array_key_exists('body', $attributes)) {
            $attributes['body'] = HtmlSanitizer::clean($attributes['body']);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncRelations(Article $article, array $data): void
    {
        if (array_key_exists('category_ids', $data)) {
            $ids = collect($data['category_ids'] ?? [])
                ->reject(fn ($id) => (int) $id === (int) $article->primary_category_id)
                ->unique()->values()->all();
            $article->categories()->sync($ids);
        }

        if (array_key_exists('tags', $data)) {
            $tagIds = collect($data['tags'] ?? [])
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique(fn ($name) => Str::lower($name))
                ->map(function (string $name) {
                    $slug = Str::slug($name);

                    return Tag::query()->firstOrCreate(['slug' => $slug], ['name' => $name])->id;
                })
                ->values()->all();
            $article->tags()->sync($tagIds);
        }
    }

    private function loadFull(Article $article): Article
    {
        return $article->load([...self::RELATIONS, 'categories:id,name,slug', 'tags:id,name,slug', 'editor:id,name', 'revisions']);
    }
}
