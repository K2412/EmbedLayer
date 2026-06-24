<?php

declare(strict_types=1);

namespace App\Analytics\Semantic\Contracts;

use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Semantic\DTOs\SemanticResult;

/**
 * Provider-agnostic semantic layer entry point. The application talks to the
 * semantic layer exclusively through this interface, regardless of whether
 * the underlying provider is internal (EmbedLayer-managed metadata + dialect
 * compiler) or external (Cube, dbt MetricFlow, etc.).
 */
interface SemanticProvider
{
    /**
     * @return array<string, mixed>
     */
    public function listModels(ProviderContext $context): array;

    /**
     * @return array<string, mixed>
     */
    public function listFields(ProviderContext $context, string $modelName): array;

    public function run(ProviderContext $context, SemanticQuery $query): SemanticResult;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array;
}
