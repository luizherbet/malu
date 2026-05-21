<?php

namespace App\Models;

use App\Enums\DownloadStatus;
use Database\Factories\DownloadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'url',
    'section',
    'download_playlist',
    'status',
    'progress',
    'format',
    'quality',
    'file_path',
    'error',
    'user_id',
    'finished_at',
])]
class Download extends Model
{
    /** @use HasFactory<DownloadFactory> */
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'download_playlist' => false,
        'status' => 'queued',
        'progress' => 0,
        'format' => 'mp4',
        'quality' => 'best',
    ];

    protected function casts(): array
    {
        return [
            'download_playlist' => 'boolean',
            'status' => DownloadStatus::class,
            'progress' => 'integer',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [DownloadStatus::Done, DownloadStatus::Failed], true);
    }
}
