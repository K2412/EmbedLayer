<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Queries;

use App\Analytics\Compiler\CompiledQuery;
use App\Analytics\Compiler\InternalQueryCompiler;
use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Models\SemanticModel;

final readonly class CompileInternalSemanticQuery
{
    public function __construct(private InternalQueryCompiler $compiler) {}

    public function handle(
        SemanticModel $model,
        ProviderContext $context,
        SemanticQuery $query,
    ): CompiledQuery {
        return $this->compiler->compile($model, $query, $context);
    }
}
