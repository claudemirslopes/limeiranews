<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BreakingNews;
use Illuminate\Http\JsonResponse;

class BreakingNewsController extends Controller
{
    public function current(): JsonResponse
    {
        $item = BreakingNews::query()
            ->current()
            ->with(['article' => fn ($q) => $q->published()->select('id', 'slug', 'title')])
            ->latest('starts_at')
            ->latest('id')
            ->first();

        if ($item === null) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'id' => $item->id,
                'headline' => $item->headline,
                'position' => $item->position,
                'article_slug' => $item->article?->slug,
                'starts_at' => $item->starts_at?->toIso8601String(),
                'ends_at' => $item->ends_at?->toIso8601String(),
            ],
        ]);
    }
}
