<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $sitemap = rtrim(config('app.url'), '/').'/sitemap.xml';

        $body = <<<TXT
User-agent: *
Allow: /
Disallow: /admin
Disallow: /api/

Sitemap: {$sitemap}
TXT;

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $base = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');

        $urls = [];
        $urls[] = ['loc' => $base.'/', 'changefreq' => 'hourly', 'priority' => '1.0'];

        Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at'])
            ->each(function (Category $category) use (&$urls, $base) {
                $urls[] = [
                    'loc' => $base.'/categoria/'.$category->slug,
                    'lastmod' => optional($category->updated_at)?->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ];
            });

        Article::query()
            ->where('status', ArticleStatus::Published)
            ->where('allow_index', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit(1000)
            ->get(['slug', 'updated_at', 'published_at'])
            ->each(function (Article $article) use (&$urls, $base) {
                $urls[] = [
                    'loc' => $base.'/noticia/'.$article->slug,
                    'lastmod' => optional($article->updated_at ?? $article->published_at)?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            });

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function newsSitemap(): Response
    {
        $base = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');

        $articles = Article::query()
            ->with('primaryCategory')
            ->where('status', ArticleStatus::Published)
            ->where('allow_index', true)
            ->whereNotNull('published_at')
            ->where('published_at', '>=', now()->subDays(2))
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit(1000)
            ->get();

        $xml = view('seo.news-sitemap', [
            'articles' => $articles,
            'base' => $base,
            'publicationName' => 'Limeira News',
            'language' => 'pt',
        ])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function rss(): Response
    {
        $base = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');

        $articles = Article::query()
            ->with('author')
            ->where('status', ArticleStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        $xml = view('seo.rss', [
            'articles' => $articles,
            'base' => $base,
            'siteName' => 'Limeira News',
            'description' => 'Notícias de Limeira, região, São Paulo, Brasil e mundo.',
        ])->render();

        return response($xml, 200)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
