<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['position' => ['required', 'string', 'max:40']]);

        $campaigns = AdCampaign::query()
            ->running()
            ->where('position', $data['position'])
            ->with('advertiser:id,name')
            ->inRandomOrder()
            ->limit(3)
            ->get();

        return response()->json([
            'data' => $campaigns->map(fn (AdCampaign $ad) => [
                'id' => $ad->id,
                'name' => $ad->name,
                'advertiser' => $ad->advertiser?->name,
                'position' => $ad->position,
                'image_url' => Str::startsWith($ad->image_path, ['http://', 'https://'])
                    ? $ad->image_path
                    : asset('storage/'.$ad->image_path),
                'target_url' => $ad->target_url,
                'label' => 'Publicidade',
            ])->values(),
        ]);
    }
}
