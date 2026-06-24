<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines\Pipes;

use App\Analytics\Compiler\AccessPolicyCompiler;
use App\Analytics\Pipelines\PipelineState;

/**
 * Plan §17.2 access-policy pipe. The {@see AccessPolicyCompiler}
 * already injects required tenant rules into the compiled SQL, so for the
 * internal provider this pipe is intentionally additive — it records that
 * policy resolution happened so {@see RecordQueryRun} can surface the
 * relevant policy names in the query_shape metadata.
 *
 * This pipe stays a no-op for execution, but is the right hook to add
 * non-compiler enforcement for external providers (Cube, dbt SL) where access
 * policies become provider filters rather than compiled WHERE fragments.
 */
final class ApplyAccessPolicies
{
    public function __invoke(PipelineState $state): PipelineState
    {
        if ($state->model === null) {
            return $state;
        }

        $policies = $state->model->accessPolicies()
            ->where('is_required', true)
            ->pluck('name')
            ->all();

        if ($policies !== []) {
            $state->queryRunMetadata['applied_access_policies'] = $policies;
        }

        return $state;
    }
}
