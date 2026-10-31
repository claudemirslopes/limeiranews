<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\EditorialFixtures;
use Tests\TestCase;

class ArticlePublishPermissionTest extends TestCase
{
    use EditorialFixtures, RefreshDatabase;

    public function test_journalist_cannot_publish_own_article(): void
    {
        $journalist = $this->userWithRole(RoleName::Journalist);
        $article = $this->article($journalist);

        Sanctum::actingAs($journalist);

        $this->postJson("/api/v1/admin/articles/{$article->id}/publish")->assertForbidden();

        $this->assertSame(ArticleStatus::Draft, $article->fresh()->status);
    }

    public function test_journalist_can_submit_own_draft_for_review(): void
    {
        $journalist = $this->userWithRole(RoleName::Journalist);
        $article = $this->article($journalist);

        Sanctum::actingAs($journalist);

        $this->postJson("/api/v1/admin/articles/{$article->id}/submit-review")
            ->assertOk()
            ->assertJsonPath('data.status', 'in_review');
    }

    public function test_journalist_cannot_edit_or_submit_someone_elses_article(): void
    {
        $owner = $this->userWithRole(RoleName::Journalist);
        $other = $this->userWithRole(RoleName::Journalist);
        $article = $this->article($owner);

        Sanctum::actingAs($other);

        $this->putJson("/api/v1/admin/articles/{$article->id}", ['title' => 'Invadida'])->assertForbidden();
        $this->postJson("/api/v1/admin/articles/{$article->id}/submit-review")->assertForbidden();
    }

    public function test_journalist_cannot_edit_article_already_in_review(): void
    {
        $journalist = $this->userWithRole(RoleName::Journalist);
        $article = $this->article($journalist, ArticleStatus::InReview);

        Sanctum::actingAs($journalist);

        $this->putJson("/api/v1/admin/articles/{$article->id}", ['title' => 'Mudança'])->assertForbidden();
    }

    public function test_editor_can_publish_and_article_becomes_public(): void
    {
        $journalist = $this->userWithRole(RoleName::Journalist);
        $editor = $this->userWithRole(RoleName::Editor);
        $article = $this->article($journalist, ArticleStatus::InReview);

        Sanctum::actingAs($editor);

        // in_review -> published is not allowed directly; it must be approved first.
        $this->postJson("/api/v1/admin/articles/{$article->id}/publish")->assertStatus(422);

        $this->postJson("/api/v1/admin/articles/{$article->id}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->postJson("/api/v1/admin/articles/{$article->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $article->refresh();
        $this->assertNotNull($article->published_at);
        $this->assertSame($editor->id, $article->editor_id);

        $this->getJson('/api/v1/articles/'.$article->slug)->assertOk();
    }

    public function test_admin_can_publish_directly_from_draft(): void
    {
        $journalist = $this->userWithRole(RoleName::Journalist);
        $admin = $this->userWithRole(RoleName::Admin);
        $article = $this->article($journalist);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/articles/{$article->id}/publish")->assertOk();
        $this->assertSame(ArticleStatus::Published, $article->fresh()->status);
    }

    public function test_scheduling_requires_a_future_date(): void
    {
        $editor = $this->userWithRole(RoleName::Editor);
        $article = $this->article($editor);

        Sanctum::actingAs($editor);

        $this->postJson("/api/v1/admin/articles/{$article->id}/schedule", ['scheduled_at' => now()->subDay()->toDateTimeString()])
            ->assertStatus(422);

        $this->postJson("/api/v1/admin/articles/{$article->id}/schedule", ['scheduled_at' => now()->addDay()->toDateTimeString()])
            ->assertOk()
            ->assertJsonPath('data.status', 'scheduled');
    }

    public function test_store_creates_draft_with_sanitized_body_and_ignores_status(): void
    {
        $journalist = $this->userWithRole(RoleName::Journalist);
        $category = $this->category();

        Sanctum::actingAs($journalist);

        $response = $this->postJson('/api/v1/admin/articles', [
            'title' => 'Prefeitura anuncia obras',
            'body' => '<p onclick="alert(1)">Texto</p><script>alert(2)</script><a href="javascript:alert(3)">link</a>',
            'primary_category_id' => $category->id,
            'status' => 'published',
            'tags' => ['obras', 'Prefeitura'],
        ])->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.slug', 'prefeitura-anuncia-obras');

        $body = $response->json('data.body');
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('script', $body);
        $this->assertStringNotContainsString('javascript:', $body);
        $this->assertCount(2, $response->json('data.tags'));
        $this->assertDatabaseHas('articles', ['author_id' => $journalist->id, 'status' => 'draft']);
    }

    public function test_html_sanitizer_keeps_allowed_markup(): void
    {
        $clean = HtmlSanitizer::clean('<h2>Título</h2><p>Texto <strong>forte</strong> <a href="https://exemplo.com" onclick="x()">link</a></p><iframe src="x"></iframe>');

        $this->assertStringContainsString('<h2>Título</h2>', $clean);
        $this->assertStringContainsString('<strong>forte</strong>', $clean);
        $this->assertStringContainsString('href="https://exemplo.com"', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
    }

    public function test_duplicate_creates_a_new_draft(): void
    {
        $editor = $this->userWithRole(RoleName::Editor);
        $article = $this->article($editor, ArticleStatus::Published);

        Sanctum::actingAs($editor);

        $this->postJson("/api/v1/admin/articles/{$article->id}/duplicate")
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_dashboard_returns_real_counts(): void
    {
        $editor = $this->userWithRole(RoleName::Editor);
        $this->article($editor, ArticleStatus::Published);
        $this->article($editor, ArticleStatus::Draft);
        $this->article($editor, ArticleStatus::Draft);

        Sanctum::actingAs($editor);

        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.articles.total', 3)
            ->assertJsonPath('data.articles.by_status.draft', 2)
            ->assertJsonPath('data.articles.by_status.published', 1);
    }
}
