<?php

namespace App\Services;

use App\Enums\DownloadStatus;
use App\Exceptions\DownloadQuotaExceededException;
use App\Models\Download;
use Illuminate\Database\Eloquent\Builder;

class DownloadQuotaService
{
    /**
     * @return array{
     *     active: int,
     *     max_active: int,
     *     today: int,
     *     max_per_day: int,
     *     can_start: bool,
     * }
     */
    public function snapshot(?int $userId): array
    {
        $maxActive = $this->maxActive();
        $maxPerDay = $this->maxPerDay();
        $active = $this->activeCount($userId);
        $today = $this->dailyCount($userId);

        return [
            'active' => $active,
            'max_active' => $maxActive,
            'today' => $today,
            'max_per_day' => $maxPerDay,
            'can_start' => $active < $maxActive && $today < $maxPerDay,
        ];
    }

    public function assertCanStart(?int $userId): void
    {
        $snapshot = $this->snapshot($userId);

        if ($snapshot['active'] >= $snapshot['max_active']) {
            throw new DownloadQuotaExceededException(
                "Limite de downloads simultâneos atingido ({$snapshot['max_active']}). "
                .'Aguarde a faixa atual terminar antes de iniciar outra.',
            );
        }

        if ($snapshot['today'] >= $snapshot['max_per_day']) {
            throw new DownloadQuotaExceededException(
                "Limite diário de downloads atingido ({$snapshot['max_per_day']} por dia). "
                .'Tente novamente amanhã.',
            );
        }
    }

    public function maxActive(): int
    {
        return max(1, (int) config('malu.downloads.max_active', 1));
    }

    public function maxPerDay(): int
    {
        return max(1, (int) config('malu.downloads.max_per_day', 20));
    }

    public function activeCount(?int $userId): int
    {
        return $this->queryFor($userId)
            ->whereIn('status', [DownloadStatus::Queued, DownloadStatus::Processing])
            ->count();
    }

    public function dailyCount(?int $userId): int
    {
        return $this->queryFor($userId)
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * @return Builder<Download>
     */
    private function queryFor(?int $userId): Builder
    {
        $query = Download::query();

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query;
    }
}
