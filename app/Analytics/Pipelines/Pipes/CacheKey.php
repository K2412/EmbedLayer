<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines\Pipes;

use App\Analytics\Pipelines\PipelineState;

/**
 * Deterministic cache-key derivation for semantic-query results. The key is a
 * sha256 of a canonical JSON object that includes everything which can change
 * the result rows: tenant, project, external account, chart, and the full
 * semantic query shape.
 */
final class CacheKey
{
    public static function for(PipelineState $state): string
    {
        $payload = [
            'organization_id' => $state->context->organizationId,
            'project_id' => $state->context->projectId,
            'external_account_id' => $state->context->externalAccountId,
            'chart_id' => $state->chart?->id,
            'query' => $state->query->jsonSerialize(),
        ];

        return hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
