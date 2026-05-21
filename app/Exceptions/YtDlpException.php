<?php

namespace App\Exceptions;

use Exception;

class YtDlpException extends Exception
{
    public static function fromProcess(int $exitCode, string $output): self
    {
        $message = trim($output) !== ''
            ? trim($output)
            : "yt-dlp exited with code {$exitCode}.";

        return new self($message);
    }
}
