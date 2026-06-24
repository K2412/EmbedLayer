<?php

declare(strict_types=1);

namespace App\Analytics\Embeds;

use App\Analytics\Support\Exceptions\InvalidEmbedTokenException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/**
 * Encodes and decodes EmbedLayer embed JWTs (Plan §11.2). Uses HS256 with a
 * single shared signing key resolved from {@see config('embedlayer.embed_signing_key')}.
 * Wraps every firebase/php-jwt failure as {@see InvalidEmbedTokenException} so
 * callers never see the underlying library's exception types.
 */
final readonly class EmbedTokenManager
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private string $signingKey,
        private int $defaultTtlSeconds = 300,
    ) {
        if ($this->signingKey === '') {
            throw new InvalidEmbedTokenException(
                'EmbedLayer embed signing key is not configured.',
            );
        }
    }

    public function generate(EmbedTokenPayload $payload, ?int $ttlSeconds = null): string
    {
        $now = time();
        $ttl = $ttlSeconds ?? $this->defaultTtlSeconds;

        $iat = $payload->iat ?? $now;
        $exp = $payload->exp ?? $iat + $ttl;

        $claims = $payload->toClaims();
        $claims['iat'] = $iat;
        $claims['exp'] = $exp;

        return JWT::encode($claims, $this->signingKey, self::ALGORITHM);
    }

    public function decode(string $token): EmbedTokenPayload
    {
        try {
            $decoded = JWT::decode($token, new Key($this->signingKey, self::ALGORITHM));
        } catch (Throwable $e) {
            throw new InvalidEmbedTokenException(
                'Embed token could not be decoded: '.$e->getMessage(),
                previous: $e,
            );
        }

        $claims = json_decode((string) json_encode($decoded), associative: true);

        if (! is_array($claims)) {
            throw new InvalidEmbedTokenException('Embed token payload is not an object.');
        }

        return EmbedTokenPayload::fromClaims($claims);
    }
}
