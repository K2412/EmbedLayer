<?php

declare(strict_types=1);

namespace App\Analytics\Embeds;

use App\Analytics\Support\Exceptions\InvalidEmbedTokenException;
use Firebase\JWT\JWT;

/**
 * Structured representation of an embed JWT's claims (Plan §11.1). The DTO is
 * the only thing the rest of the application reads — raw arrays from the JWT
 * library never leak past {@see EmbedTokenManager}.
 */
final readonly class EmbedTokenPayload
{
    /**
     * @param  list<string>  $allowedDashboardIds
     * @param  list<string>  $allowedModelNames
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $theme
     */
    public function __construct(
        public string $iss,
        public ?string $sub,
        public string $organizationId,
        public string $projectId,
        public string $embedId,
        public ?string $externalAccountId,
        public array $allowedDashboardIds,
        public array $allowedModelNames,
        public array $filters,
        public array $theme,
        public ?int $iat = null,
        public ?int $exp = null,
        public ?string $jti = null,
    ) {}

    /**
     * Build a payload from the decoded JWT claim map. Required identifiers must
     * be present and non-empty; anything else falls back to safe defaults.
     *
     * @param  array<string, mixed>  $claims
     */
    public static function fromClaims(array $claims): self
    {
        $required = ['iss', 'organization_id', 'project_id', 'embed_id'];
        $missing = [];

        foreach ($required as $key) {
            $value = $claims[$key] ?? null;
            if (! is_string($value) || $value === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new InvalidEmbedTokenException(
                'Embed token is missing required claim(s): '.implode(', ', $missing).'.',
            );
        }

        return new self(
            iss: (string) $claims['iss'],
            sub: self::nullableString($claims['sub'] ?? null),
            organizationId: (string) $claims['organization_id'],
            projectId: (string) $claims['project_id'],
            embedId: (string) $claims['embed_id'],
            externalAccountId: self::nullableString($claims['external_account_id'] ?? null),
            allowedDashboardIds: self::stringList($claims['allowed_dashboard_ids'] ?? []),
            allowedModelNames: self::stringList($claims['allowed_model_names'] ?? []),
            filters: self::stringKeyedArray($claims['filters'] ?? []),
            theme: self::stringKeyedArray($claims['theme'] ?? []),
            iat: self::nullableInt($claims['iat'] ?? null),
            exp: self::nullableInt($claims['exp'] ?? null),
            jti: self::nullableString($claims['jti'] ?? null),
        );
    }

    /**
     * Snake-case claim map ready for {@see JWT::encode()}.
     *
     * @return array<string, mixed>
     */
    public function toClaims(): array
    {
        $claims = [
            'iss' => $this->iss,
            'sub' => $this->sub,
            'organization_id' => $this->organizationId,
            'project_id' => $this->projectId,
            'embed_id' => $this->embedId,
            'external_account_id' => $this->externalAccountId,
            'allowed_dashboard_ids' => $this->allowedDashboardIds,
            'allowed_model_names' => $this->allowedModelNames,
            'filters' => (object) $this->filters,
            'theme' => (object) $this->theme,
        ];

        if ($this->iat !== null) {
            $claims['iat'] = $this->iat;
        }

        if ($this->exp !== null) {
            $claims['exp'] = $this->exp;
        }

        if ($this->jti !== null) {
            $claims['jti'] = $this->jti;
        }

        return $claims;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $items = is_array($value) ? array_values($value) : [];
        $out = [];

        foreach ($items as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private static function stringKeyedArray(mixed $value): array
    {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $key => $entry) {
            if (is_string($key)) {
                $out[$key] = $entry;
            }
        }

        return $out;
    }
}
