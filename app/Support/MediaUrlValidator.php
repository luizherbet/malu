<?php

namespace App\Support;

class MediaUrlValidator
{
    private const BLOCKED_SCHEMES = ['file', 'ftp', 'gopher', 'data', 'javascript'];

    public static function sanitize(string $url): string
    {
        $url = trim($url);
        $url = str_replace(["\0", "\r", "\n", "\t"], '', $url);

        return $url;
    }

    public static function isAllowed(string $url): bool
    {
        $url = self::sanitize($url);

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        if (in_array($scheme, self::BLOCKED_SCHEMES, true)) {
            return false;
        }

        $host = strtolower($parts['host']);

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! self::isPrivateOrReservedIp($host);
        }

        $resolved = dns_get_record($host, DNS_A + DNS_AAAA);

        if ($resolved === false || $resolved === []) {
            return true;
        }

        foreach ($resolved as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if ($ip !== null && self::isPrivateOrReservedIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public static function isSafeStoragePath(string $path): bool
    {
        if ($path === '' || str_contains($path, '..')) {
            return false;
        }

        return str_starts_with($path, 'downloads/');
    }

    private static function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }
}
