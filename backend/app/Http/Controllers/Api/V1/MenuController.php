<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    public function show(string $location): JsonResponse
    {
        $menu = Menu::query()
            ->where('location', $location)
            ->with(['rootItems.children', 'rootItems.category:id,slug'])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'location' => $menu->location,
                'items' => $menu->rootItems->map(fn (MenuItem $item) => $this->item($item))->values(),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function item(MenuItem $item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $item->url,
            'target' => $item->target,
            'category_slug' => $item->category?->slug,
            'children' => $item->children->map(fn (MenuItem $child) => [
                'id' => $child->id,
                'label' => $child->label,
                'url' => $child->url,
                'target' => $child->target,
            ])->values(),
        ];
    }
}
