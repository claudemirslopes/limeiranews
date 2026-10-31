<?php

namespace Tests\Concerns;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;

trait EditorialFixtures
{
    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function userWithRole(RoleName $role, array $attributes = []): User
    {
        if (! Role::query()->where('name', $role->value)->exists()) {
            $this->seedRoles();
        }

        return User::factory()->create(array_merge([
            'role_id' => Role::query()->where('name', $role->value)->value('id'),
            'is_active' => true,
        ], $attributes));
    }

    protected function category(string $name = 'Limeira'): Category
    {
        return Category::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'type' => Category::TYPE_GEOGRAPHIC, 'is_active' => true],
        );
    }

    protected function article(User $author, ArticleStatus $status = ArticleStatus::Draft, array $attributes = []): Article
    {
        $title = $attributes['title'] ?? 'Matéria '.Str::random(6);

        $article = new Article([
            'title' => $title,
            'summary' => 'Resumo da matéria.',
            'body' => '<p>Texto da matéria de teste.</p>',
            'primary_category_id' => $this->category()->id,
        ]);
        $article->slug = $attributes['slug'] ?? Str::slug($title).'-'.Str::random(4);
        $article->author_id = $author->id;
        $article->status = $status;
        $article->published_at = $attributes['published_at']
            ?? ($status === ArticleStatus::Published ? now()->subHour() : null);
        $article->save();

        return $article;
    }
}
