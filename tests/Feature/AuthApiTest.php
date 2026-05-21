<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Models\User;
use App\Services\JwtService;
use App\Services\MaluAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'malu.require_auth' => true,
            'malu.auth_email' => 'malu@malu.com',
            'malu.auth_password' => 'test-secret',
            'malu.jwt_secret' => 'test-jwt-secret-key-must-be-at-least-32-chars',
        ]);
    }

    public function test_config_endpoint_returns_login_email(): void
    {
        $response = $this->getJson('/api/config');

        $response->assertOk()
            ->assertJsonPath('data.require_auth', true)
            ->assertJsonPath('data.login_email', 'malu@malu.com');
    }

    public function test_login_returns_jwt_token_for_configured_user(): void
    {
        app(MaluAuthService::class)->ensureUser();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'malu@malu.com',
            'password' => 'test-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'malu@malu.com')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_rejects_wrong_email_or_password(): void
    {
        app(MaluAuthService::class)->ensureUser();

        $this->postJson('/api/auth/login', [
            'email' => 'other@malu.com',
            'password' => 'test-secret',
        ])->assertUnprocessable();

        $this->postJson('/api/auth/login', [
            'email' => 'malu@malu.com',
            'password' => 'wrong',
        ])->assertUnprocessable();
    }

    public function test_protected_routes_require_bearer_token(): void
    {
        $this->postJson('/api/jobs', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_and_view_own_job(): void
    {
        Queue::fake();

        $user = app(MaluAuthService::class)->ensureUser();
        $token = app(JwtService::class)->issue($user);

        $create = $this->withToken($token)
            ->postJson('/api/jobs', [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->assertCreated();

        $this->assertSame($user->id, Download::first()->user_id);

        $this->withToken($token)
            ->getJson("/api/jobs/{$create->json('data.id')}")
            ->assertOk();
    }

    public function test_user_cannot_view_another_users_download(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $download = Download::factory()->done()->create([
            'user_id' => $owner->id,
        ]);

        $token = app(JwtService::class)->issue($other);

        $this->withToken($token)
            ->getJson("/api/jobs/{$download->id}")
            ->assertForbidden();
    }

    public function test_auth_user_endpoint_returns_current_user(): void
    {
        $user = app(MaluAuthService::class)->ensureUser();
        $token = app(JwtService::class)->issue($user);

        $this->withToken($token)
            ->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'malu@malu.com');
    }
}
