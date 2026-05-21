<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_endpoint_returns_auth_flags(): void
    {
        config([
            'malu.require_auth' => true,
            'malu.allow_registration' => false,
        ]);

        $response = $this->getJson('/api/config');

        $response->assertOk()
            ->assertJsonPath('data.require_auth', true)
            ->assertJsonPath('data.allow_registration', false);
    }

    public function test_login_and_user_session(): void
    {
        $user = User::factory()->create([
            'email' => 'user@malu.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'user@malu.test',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_jobs_require_auth_when_enabled(): void
    {
        config(['malu.require_auth' => true]);

        $this->postJson('/api/jobs', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_job_when_auth_required(): void
    {
        config(['malu.require_auth' => true]);
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/jobs', [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->assertCreated();

        $this->assertSame($user->id, Download::first()->user_id);
    }

    public function test_user_cannot_view_another_users_download(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $download = Download::factory()->done()->create([
            'user_id' => $owner->id,
        ]);

        $this->actingAs($other)
            ->getJson("/api/jobs/{$download->id}")
            ->assertForbidden();
    }

    public function test_guest_can_view_anonymous_download(): void
    {
        config(['malu.require_auth' => false]);

        $download = Download::factory()->processing()->create([
            'user_id' => null,
        ]);

        $this->getJson("/api/jobs/{$download->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $download->id);
    }
}
