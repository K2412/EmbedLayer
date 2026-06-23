<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Queries;

use App\Analytics\Compiler\CompiledQuery;
use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Models\SemanticModel;
use RuntimeException;

final readonly class CompileInternalSemanticQuery
{
    public function handle(
        SemanticModel $model,
        ProviderContext $context,
        SemanticQuery $query,
    ): CompiledQuery {
        throw new RuntimeException('CompileInternalSemanticQuery is implemented by a later milestone (M4).');
    }
}
