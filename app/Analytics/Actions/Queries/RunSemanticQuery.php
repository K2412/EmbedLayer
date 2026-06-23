<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Queries;

use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Semantic\DTOs\SemanticResult;
use RuntimeException;

final readonly class RunSemanticQuery
{
    public function handle(ProviderContext $context, SemanticQuery $query): SemanticResult
    {
        throw new RuntimeException('RunSemanticQuery is implemented by a later milestone (M4).');
    }
}
