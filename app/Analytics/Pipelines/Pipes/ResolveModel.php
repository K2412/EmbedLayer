<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines\Pipes;

use App\Analytics\Pipelines\PipelineState;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Models\SemanticModel;

/**
 * Locates the {@see SemanticModel} referenced by the query, scoped to the
 * caller's organization (Plan §10, §17.2). Models outside the token's
 * `allowed_model_names` list are rejected here so we never compile or execute
 * a query for an unauthorized model.
 */
final class ResolveModel
{
    public function __invoke(PipelineState $state): PipelineState
    {
        $query = $state->query;
        $context = $state->context;

        $allowedModels = $this->allowedModelNames($context->claims);

        if ($allowedModels !== [] && ! in_array($query->model, $allowedModels, true)) {
            throw new SemanticModelValidationException([
                "Model `{$query->model}` is not in the embed token's allowed_model_names.",
            ]);
        }

        $model = SemanticModel::query()
            ->where('organization_id', $context->organizationId)
            ->where('name', $query->model)
            ->where('is_enabled', true)
            ->first();

        if ($model === null) {
            throw new SemanticModelValidationException([
                "Semantic model `{$query->model}` not found for organization.",
            ]);
        }

        $state->model = $model;

        return $state;
    }

    /**
     * @param  array<string, mixed>  $claims
     * @return list<string>
     */
    private function allowedModelNames(array $claims): array
    {
        $raw = $claims['allowed_model_names'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach (array_values($raw) as $entry) {
            if (is_string($entry) && $entry !== '') {
                $out[] = $entry;
            }
        }

        return $out;
    }
}
