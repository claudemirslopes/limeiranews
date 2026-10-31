<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleListResource;
use App\Models\AdCampaign;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $ownOnly = ! $user->isAdmin() && ! $user->isEditor();

        $articles = fn (): Builder => Article::query()
            ->when($ownOnly, fn (Builder $q) => $q->where('author_id', $user->id));

        $byStatus = $articles()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusCounts = collect(ArticleStatus::cases())
            ->mapWithKeys(fn (ArticleStatus $s) => [$s->value => (int) ($byStatus[$s->value] ?? 0)]);

        $recent = $articles()
            ->with(['primaryCategory:id,name,slug', 'author:id,name,public_name,slug'])
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return response()->json([
            'data' => [
                'articles' => [
                    'total' => $statusCounts->sum(),
                    'by_status' => $statusCounts,
                    'published_today' => $articles()->published()->whereDate('published_at', today())->count(),
                    'published_this_month' => $articles()->published()
                        ->whereBetween('published_at', [now()->startOfMonth(), now()])->count(),
                    'awaiting_review' => $articles()->where('status', ArticleStatus::InReview->value)->count(),
                    'scheduled' => $articles()->where('status', ArticleStatus::Scheduled->value)->count(),
                ],
                'categories' => Category::query()->count(),
                'media' => Media::query()->count(),
                'newsletter' => $user->isAdmin() || $user->isEditor() ? [
                    'confirmed' => NewsletterSubscriber::query()->whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
                    'pending' => NewsletterSubscriber::query()->whereNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
                ] : null,
                'users' => $user->isAdmin() ? User::query()->where('is_active', true)->count() : null,
                'active_campaigns' => $user->isAdmin() ? AdCampaign::query()->running()->count() : null,
                'recent_articles' => ArticleListResource::collection($recent)->resolve($request),
            ],
        ]);
    }
}
