<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomepageSectionResource;
use App\Services\HomepageService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HomepageController extends Controller
{
    public function index(HomepageService $homepage): AnonymousResourceCollection
    {
        return HomepageSectionResource::collection($homepage->sections());
    }
}
