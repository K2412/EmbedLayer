<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines\Pipes;

use App\Analytics\Pipelines\PipelineState;
use App\Analytics\Semantic\DTOs\SemanticResult;

/**
 * Ensures the {@see SemanticResult::$columns} list is always shaped as
 * `{key, label, type}` tuples. The internal compiler already emits this shape,
 * but external providers may produce thinner column metadata — this pipe
 * canonicalises so the embed runtime never needs branching logic.
 */
final class NormalizeResult
{
    public function __invoke(PipelineState $state): PipelineState
    {
        $result = $state->result;

        if ($result === null) {
            return $state;
        }

        $columns = [];
        foreach ($result->columns as $column) {
            if (! is_array($column)) {
                continue;
            }

            $key = isset($column['key']) ? (string) $column['key'] : '';
            $label = isset($column['label']) ? (string) $column['label'] : $key;
            $type = isset($column['type']) ? (string) $column['type'] : 'unknown';

            if ($key === '') {
                continue;
            }

            $columns[] = ['key' => $key, 'label' => $label, 'type' => $type];
        }

        if ($columns === $result->columns) {
            return $state;
        }

        $state->result = new SemanticResult(
            columns: $columns,
            rows: $result->rows,
            metadata: $result->metadata,
        );

        return $state;
    }
}
