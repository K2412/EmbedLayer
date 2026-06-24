<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Embeds;

use App\Analytics\Embeds\EmbedTokenManager;
use App\Analytics\Embeds\EmbedTokenPayload;
use App\Models\Embed;
use App\Models\EmbedToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Mints an embed JWT for a saved {@see Embed} (Plan §11.3). Token claims are
 * derived from the saved embed config — the dashboard scope and TTL are NOT
 * accepted from the caller, only from the {@see Embed} record. Caller-supplied
 * `$extraClaims` may only specify the external account, sub, allowed model
 * names, and per-session filters.
 *
 * Each generated token is also persisted as an {@see EmbedToken} so we have an
 * audit trail (jti, payload hash, validity window) that can be used for
 * revocation in a later milestone.
 */
final readonly class GenerateEmbedToken
{
    public function __construct(private EmbedTokenManager $tokens) {}

    /**
     * @param  array<string, mixed>  $extraClaims
     */
    public function handle(Embed $embed, array $extraClaims = []): string
    {
        $embed->loadMissing('dashboard');

        $dashboard = $embed->dashboard;
        $sub = $this->stringOrNull($extraClaims['sub'] ?? null);
        $externalAccountId = $this->stringOrNull($extraClaims['external_account_id'] ?? null);
        $allowedModelNames = $this->stringList($extraClaims['allowed_model_names'] ?? []);
        $filters = $this->stringKeyed($extraClaims['filters'] ?? []);

        $now = time();
        $ttl = (int) $embed->default_ttl_seconds;
        $exp = $now + $ttl;
        $jti = (string) Str::ulid();

        $payload = new EmbedTokenPayload(
            iss: 'embedlayer',
            sub: $sub,
            organizationId: $embed->organization_id,
            projectId: $dashboard->analytics_project_id,
            embedId: $embed->id,
            externalAccountId: $externalAccountId,
            allowedDashboardIds: [$embed->dashboard_id],
            allowedModelNames: $allowedModelNames,
            filters: $filters,
            theme: is_array($embed->theme) ? $embed->theme : [],
            iat: $now,
            exp: $exp,
            jti: $jti,
        );

        $token = $this->tokens->generate($payload, $ttl);

        EmbedToken::query()->create([
            'embed_id' => $embed->id,
            'jti' => $jti,
            'external_account_id' => $externalAccountId,
            'payload_hash' => $this->payloadHash($payload),
            'issued_at' => Carbon::createFromTimestamp($now),
            'expires_at' => Carbon::createFromTimestamp($exp),
        ]);

        return $token;
    }

    private function payloadHash(EmbedTokenPayload $payload): string
    {
        $claims = $payload->toClaims();
        unset($claims['iat'], $claims['exp'], $claims['jti']);

        ksort($claims);

        return hash('sha256', (string) json_encode($claims));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach (array_values($value) as $entry) {
            if (is_string($entry) && $entry !== '') {
                $out[] = $entry;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function stringKeyed(mixed $value): array
    {
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
