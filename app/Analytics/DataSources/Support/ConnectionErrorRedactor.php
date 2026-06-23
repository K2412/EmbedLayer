<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\Support;

/**
 * Scrubs likely-secret tokens out of driver error messages before they're
 * surfaced to users. Plan §17.4: avoid logging SQL bindings or credentials.
 */
final class ConnectionErrorRedactor
{
    private const SECRET_KEYS = ['password', 'token', 'secret', 'apikey', 'api_key', 'auth', 'authorization', 'pwd'];

    public static function redact(string $message): string
    {
        $patterns = [];

        foreach (self::SECRET_KEYS as $key) {
            $patterns[] = '/('.preg_quote($key, '/').'\s*[:=]\s*)([^\s,;)]+)/i';
        }

        return preg_replace($patterns, '$1[REDACTED]', $message) ?? $message;
    }
}
