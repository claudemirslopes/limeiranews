<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\AuditLog;
use App\Models\Category;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(private readonly SlugService $slugs) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('manage-taxonomy');

        $categories = Category::query()
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryResource
    {
        Gate::authorize('manage-taxonomy');

        return new CategoryResource($category->load('children'));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manage-taxonomy');

        $data = $this->validated($request);
        $data['slug'] = $this->slugs->unique($data['slug'] ?? $data['name'], Category::class);

        $category = Category::query()->create($data);
        AuditLog::record('category.created', $category, [], $request->user(), $request);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(Request $request, Category $category): CategoryResource
    {
        Gate::authorize('manage-taxonomy');

        $data = $this->validated($request, $category);

        if (isset($data['slug']) && $data['slug'] !== $category->slug) {
            $data['slug'] = $this->slugs->unique($data['slug'], Category::class, $category->id);
        }

        $category->update($data);
        AuditLog::record('category.updated', $category, ['fields' => array_keys($data)], $request->user(), $request);

        return new CategoryResource($category->refresh());
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        Gate::authorize('manage-taxonomy');

        if ($category->articles()->exists() || $category->children()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir uma categoria com matérias ou subcategorias. Desative-a ou mova o conteúdo antes.',
            ], 422);
        }

        AuditLog::record('category.deleted', $category, [], $request->user(), $request);
        $category->delete();

        return response()->json(['message' => 'Categoria removida.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        $required = $category ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'parent_id' => [
                'nullable', 'integer', 'exists:categories,id',
                Rule::notIn($category ? [$category->id] : []),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'type' => [$required, Rule::in([Category::TYPE_GEOGRAPHIC, Category::TYPE_EDITORIAL])],
        ]);
    }
}
