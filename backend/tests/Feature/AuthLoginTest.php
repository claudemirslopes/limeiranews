<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EditorialFixtures;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use EditorialFixtures, RefreshDatabase;

    public function test_user_can_login_and_access_me_with_bearer_token(): void
    {
        $user = $this->userWithRole(RoleName::Editor, ['email' => 'editor@teste.com', 'password' => 'Senha!12345']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'editor@teste.com',
            'password' => 'Senha!12345',
        ])->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email', 'role' => ['name']]])
            ->assertJsonPath('user.role.name', 'editor');

        $this->withToken($response->json('token'))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->userWithRole(RoleName::Editor, ['email' => 'editor@teste.com', 'password' => 'Senha!12345']);

        $this->postJson('/api/v1/auth/login', ['email' => 'editor@teste.com', 'password' => 'errada'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->userWithRole(RoleName::Editor, [
            'email' => 'inativo@teste.com', 'password' => 'Senha!12345', 'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', ['email' => 'inativo@teste.com', 'password' => 'Senha!12345'])
            ->assertStatus(422);
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = $this->userWithRole(RoleName::Journalist, ['password' => 'Senha!12345']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/admin/articles')->assertUnauthorized();
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
