<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EditorialFixtures;
use Tests\TestCase;

class PublicArticleTest extends TestCase
{
    use EditorialFixtures, RefreshDatabase;

    public function test_only_published_articles_are_listed(): void
    {
        $author = $this->userWithRole(RoleName::Journalist);
        $published = $this->article($author, ArticleStatus::Published, ['title' => 'Notícia publicada']);
        $this->article($author, ArticleStatus::Draft, ['title' => 'Rascunho secreto']);
        $this->article($author, ArticleStatus::InReview, ['title' => 'Em revisão']);
        $this->article($author, ArticleStatus::Published, ['title' => 'Publicação futura', 'published_at' => now()->addDay()]);

        $response = $this->getJson('/api/v1/articles')->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $published->slug);
        $this->assertStringNotContainsString('Rascunho secreto', $response->getContent());
    }

    public function test_draft_article_is_not_visible_by_slug(): void
    {
        $author = $this->userWithRole(RoleName::Journalist);
        $draft = $this->article($author, ArticleStatus::Draft);

        $this->getJson('/api/v1/articles/'.$draft->slug)->assertNotFound();
    }

    public function test_published_article_is_visible_without_internal_metrics(): void
    {
        $author = $this->userWithRole(RoleName::Journalist);
        $article = $this->article($author, ArticleStatus::Published);

        $this->getJson('/api/v1/articles/'.$article->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', $article->slug)
            ->assertJsonMissingPath('data.view_count')
            ->assertJsonMissingPath('data.status');
    }

    public function test_search_only_returns_published_articles(): void
    {
        $author = $this->userWithRole(RoleName::Journalist);
        $this->article($author, ArticleStatus::Published, ['title' => 'Obras de drenagem em Limeira']);
        $this->article($author, ArticleStatus::Draft, ['title' => 'Obras de drenagem secretas']);

        $this->getJson('/api/v1/search?q=drenagem')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_homepage_sections_only_expose_published_articles(): void
    {
        $author = $this->userWithRole(RoleName::Journalist);
        $this->article($author, ArticleStatus::Draft, ['title' => 'Escondida']);
        $this->article($author, ArticleStatus::Published, ['title' => 'Visível']);

        \App\Models\HomepageSection::query()->create([
            'key' => 'ultimas', 'title' => 'Últimas', 'section_type' => 'latest', 'limit' => 5, 'sort_order' => 1, 'is_visible' => true,
        ]);

        $this->getJson('/api/v1/homepage')
            ->assertOk()
            ->assertJsonCount(1, 'data.0.articles');
    }
}
