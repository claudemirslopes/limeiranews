<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomepageSectionResource;
use App\Models\AuditLog;
use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class HomepageSectionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('manage-homepage');

        return HomepageSectionResource::collection(
            HomepageSection::query()
                ->with(['category:id,name,slug', 'region:id,name,slug'])
                ->orderBy('sort_order')
                ->get()
        );
    }

    public function update(Request $request, HomepageSection $homepageSection): HomepageSectionResource
    {
        Gate::authorize('manage-homepage');

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:160'],
            'section_type' => ['sometimes', Rule::in([
                HomepageSection::TYPE_HERO, HomepageSection::TYPE_LATEST, HomepageSection::TYPE_CATEGORY,
                HomepageSection::TYPE_REGION, HomepageSection::TYPE_FEATURED, HomepageSection::TYPE_MOST_READ,
            ])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_visible' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $homepageSection->update($data);
        AuditLog::record('homepage_section.updated', $homepageSection, ['fields' => array_keys($data)], $request->user(), $request);

        return new HomepageSectionResource($homepageSection->refresh()->load(['category:id,name,slug', 'region:id,name,slug']));
    }
}
