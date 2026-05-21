<?php

namespace Tests\Unit;

use App\Support\MediaUrlValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MediaUrlValidatorTest extends TestCase
{
    #[DataProvider('allowedUrlsProvider')]
    public function test_allows_public_http_urls(string $url): void
    {
        $this->assertTrue(MediaUrlValidator::isAllowed($url));
    }

    #[DataProvider('blockedUrlsProvider')]
    public function test_blocks_unsafe_urls(string $url): void
    {
        $this->assertFalse(MediaUrlValidator::isAllowed($url));
    }

    public function test_sanitize_strips_control_characters(): void
    {
        $url = "https://example.com/video\n\r";

        $this->assertSame('https://example.com/video', MediaUrlValidator::sanitize($url));
    }

    public function test_safe_storage_path(): void
    {
        $this->assertTrue(MediaUrlValidator::isSafeStoragePath('downloads/uuid/video.mp4'));
        $this->assertFalse(MediaUrlValidator::isSafeStoragePath('../etc/passwd'));
        $this->assertFalse(MediaUrlValidator::isSafeStoragePath('other/video.mp4'));
    }

    public static function allowedUrlsProvider(): array
    {
        return [
            ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['https://youtu.be/dQw4w9WgXcQ'],
            ['http://example.com/video'],
        ];
    }

    public static function blockedUrlsProvider(): array
    {
        return [
            ['file:///etc/passwd'],
            ['ftp://example.com/video'],
            ['javascript:alert(1)'],
            ['https://localhost/video'],
            ['https://127.0.0.1/video'],
            ['https://192.168.1.1/video'],
            ['https://10.0.0.5/internal'],
            ['not-a-url'],
        ];
    }
}
