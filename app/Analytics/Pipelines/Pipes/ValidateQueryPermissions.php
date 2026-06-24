<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines\Pipes;

use App\Analytics\Compiler\FieldValidator;
use App\Analytics\Pipelines\PipelineState;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;

/**
 * Belt-and-braces field validation (Plan §10, §11.3). Delegates the heavy
 * lifting to {@see FieldValidator} (existence + public flag + time-grain
 * support) and then re-checks the model-allowlist claim — `allowed_model_names`
 * is also enforced upstream in {@see ResolveModel}, but we re-assert it here so
 * the pipeline remains correct if pipes are ever reordered.
 */
final readonly class ValidateQueryPermissions
{
    public function __construct(private FieldValidator $fieldValidator) {}

    public function __invoke(PipelineState $state): PipelineState
    {
        if ($state->model === null) {
            throw new SemanticModelValidationException([
                'ValidateQueryPermissions requires a resolved semantic model.',
            ]);
        }

        $this->fieldValidator->validate($state->model, $state->query);

        $allowed = $state->context->claims['allowed_model_names'] ?? [];
        if (is_array($allowed) && $allowed !== [] && ! in_array($state->query->model, $allowed, true)) {
            throw new SemanticModelValidationException([
                "Model `{$state->query->model}` is not in the embed token's allowed_model_names.",
            ]);
        }

        return $state;
    }
}
