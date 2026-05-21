<?php

namespace Tests\Feature;

use App\Exceptions\YtDlpException;
use App\Services\PlaylistPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaylistPreviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_returns_tracks_for_playlist_url(): void
    {
        $this->mock(PlaylistPreviewService::class, function ($mock): void {
            $mock->shouldReceive('listTracks')
                ->once()
                ->andReturn([
                    [
                        'index' => 1,
                        'id' => 'abc123',
                        'title' => 'First Song',
                        'url' => 'https://www.youtube.com/watch?v=abc123',
                        'section' => null,
                    ],
                    [
                        'index' => 2,
                        'id' => 'def456',
                        'title' => 'Second Song',
                        'url' => 'https://www.youtube.com/watch?v=def456',
                        'section' => null,
                    ],
                ]);
        });

        $response = $this->postJson('/api/playlists/preview', [
            'url' => 'https://www.youtube.com/playlist?list=PLtest',
        ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data.tracks')
            ->assertJsonPath('data.tracks.0.title', 'First Song')
            ->assertJsonPath('data.tracks.1.id', 'def456');
    }

    public function test_preview_validates_url(): void
    {
        $response = $this->postJson('/api/playlists/preview', [
            'url' => 'not-valid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_preview_returns_ytdlp_error_as_422(): void
    {
        $this->mock(PlaylistPreviewService::class, function ($mock): void {
            $mock->shouldReceive('listTracks')
                ->once()
                ->andThrow(new YtDlpException('Sign in to confirm you are not a bot.'));
        });

        $response = $this->postJson('/api/playlists/preview', [
            'url' => 'https://www.youtube.com/playlist?list=PLtest',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Sign in to confirm you are not a bot.');
    }

    public function test_store_accepts_section_for_chapter_download(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $response = $this->postJson('/api/jobs', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'section' => 'Intro',
        ]);

        $response->assertCreated();

        $this->assertSame('Intro', \App\Models\Download::first()->section);
    }
}
