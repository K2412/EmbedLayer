<?php

declare(strict_types=1);

namespace App\Analytics\Actions\SemanticModels;

use App\Models\SemanticModel;
use RuntimeException;

final readonly class ValidateSemanticModel
{
    /**
     * @return list<string> list of validation error messages; empty when the model is valid
     */
    public function handle(SemanticModel $model): array
    {
        throw new RuntimeException('ValidateSemanticModel is implemented by a later milestone (M3).');
    }
}
