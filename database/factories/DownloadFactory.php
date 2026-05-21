<?php

namespace Database\Factories;

use App\Enums\DownloadStatus;
use App\Models\Download;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Download>
 */
class DownloadFactory extends Factory
{
    protected $model = Download::class;

    public function definition(): array
    {
        return [
            'url' => 'https://www.youtube.com/watch?v='.$this->faker->regexify('[A-Za-z0-9_-]{11}'),
            'status' => DownloadStatus::Queued,
            'progress' => 0,
            'format' => 'mp4',
            'quality' => 'best',
            'file_path' => null,
            'error' => null,
            'user_id' => null,
            'finished_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => DownloadStatus::Processing,
            'progress' => $this->faker->numberBetween(1, 99),
        ]);
    }

    public function done(): static
    {
        return $this->state(fn () => [
            'status' => DownloadStatus::Done,
            'progress' => 100,
            'file_path' => 'downloads/'.$this->faker->uuid().'/video.mp4',
            'finished_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => DownloadStatus::Failed,
            'error' => 'Download failed.',
            'finished_at' => now(),
        ]);
    }
}
