<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines\Pipes;

use App\Analytics\Pipelines\PipelineState;
use App\Analytics\Semantic\Providers\InternalSemanticProvider;

/**
 * Hands off to the active {@see InternalSemanticProvider} unless a cache hit
 * already supplied a result. Plan §10 step 7.
 */
final readonly class ExecuteQuery
{
    public function __construct(private InternalSemanticProvider $provider) {}

    public function __invoke(PipelineState $state): PipelineState
    {
        if ($state->cacheHit) {
            return $state;
        }

        $state->result = $this->provider->run($state->context, $state->query);

        return $state;
    }
}
