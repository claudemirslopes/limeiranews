<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\User;
use App\Policies\ArticlePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! $this->app->runningUnitTests()) {
            $root = rtrim((string) config('app.url'), '/');
            if ($root !== '') {
                URL::forceRootUrl($root);
            }
        }

        Gate::policy(Article::class, ArticlePolicy::class);

        // Back-office capabilities that are not tied to a single model.
        Gate::define('manage-taxonomy', fn (User $user) => $user->is_active && ($user->isAdmin() || $user->isEditor()));
        Gate::define('manage-homepage', fn (User $user) => $user->is_active && ($user->isAdmin() || $user->isEditor()));
        Gate::define('upload-media', fn (User $user) => $user->is_active && ($user->isAdmin() || $user->isEditor() || $user->isWriter()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('newsletter', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
