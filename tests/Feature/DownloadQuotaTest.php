<?php

namespace Tests\Feature;

use App\Enums\DownloadStatus;
use App\Models\Download;
use App\Services\JwtService;
use App\Services\MaluAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DownloadQuotaTest extends TestCase
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
            'malu.downloads.max_active' => 1,
            'malu.downloads.max_per_day' => 2,
        ]);
    }

    public function test_quota_endpoint_returns_usage(): void
    {
        $user = app(MaluAuthService::class)->ensureUser();
        $token = app(JwtService::class)->issue($user);

        Download::factory()->processing()->create(['user_id' => $user->id]);

        $this->withToken($token)
            ->getJson('/api/downloads/quota')
            ->assertOk()
            ->assertJsonPath('data.active', 1)
            ->assertJsonPath('data.max_active', 1)
            ->assertJsonPath('data.can_start', false);
    }

    public function test_store_rejects_when_max_active_reached(): void
    {
        Queue::fake();

        $user = app(MaluAuthService::class)->ensureUser();
        $token = app(JwtService::class)->issue($user);

        Download::factory()->processing()->create(['user_id' => $user->id]);

        $this->withToken($token)
            ->postJson('/api/jobs', [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->assertStatus(429)
            ->assertJsonFragment([
                'message' => 'Limite de downloads simultâneos atingido (1). Aguarde a faixa atual terminar antes de iniciar outra.',
            ]);
    }

    public function test_store_rejects_when_daily_limit_reached(): void
    {
        Queue::fake();

        $user = app(MaluAuthService::class)->ensureUser();
        $token = app(JwtService::class)->issue($user);

        Download::factory()->done()->create([
            'user_id' => $user->id,
            'created_at' => now()->subHours(2),
        ]);
        Download::factory()->done()->create([
            'user_id' => $user->id,
            'created_at' => now()->subHours(1),
        ]);

        $this->withToken($token)
            ->postJson('/api/jobs', [
                'url' => 'https://www.youtube.com/watch?v=abc12345678',
            ])
            ->assertStatus(429)
            ->assertJsonFragment([
                'message' => 'Limite diário de downloads atingido (2 por dia). Tente novamente amanhã.',
            ]);
    }

    public function test_completed_download_frees_active_slot(): void
    {
        Queue::fake();

        $user = app(MaluAuthService::class)->ensureUser();
        $token = app(JwtService::class)->issue($user);

        Download::factory()->create([
            'user_id' => $user->id,
            'status' => DownloadStatus::Done,
        ]);

        $this->withToken($token)
            ->postJson('/api/jobs', [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->assertCreated();
    }
}
