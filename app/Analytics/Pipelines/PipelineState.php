<?php

declare(strict_types=1);

namespace App\Analytics\Pipelines;

use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Semantic\DTOs\SemanticResult;
use App\Models\Chart;
use App\Models\Dashboard;
use App\Models\SemanticModel;

/**
 * Mutable state carried through the {@see QueryExecutionPipeline}. Each pipe
 * may read prior fields and write the ones it produces — keeping this
 * deliberately mutable lets pipes stay tiny invokable classes (Plan §10).
 */
final class PipelineState
{
    public ?SemanticModel $model = null;

    public ?SemanticResult $result = null;

    public ?string $cacheKey = null;

    public bool $cacheHit = false;

    public float $startedAt;

    /**
     * @var array<string, mixed>
     */
    public array $queryRunMetadata = [];

    public function __construct(
        public ProviderContext $context,
        public SemanticQuery $query,
        public ?Dashboard $dashboard = null,
        public ?Chart $chart = null,
    ) {
        $this->startedAt = microtime(true);
    }
}
