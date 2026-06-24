<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Dashboards;

use App\Analytics\Charts\ChartTypeRegistry;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Models\Chart;
use App\Models\Dashboard;
use App\Models\SemanticModel;

final readonly class AddChartToDashboard
{
    public function __construct(private ChartTypeRegistry $chartTypes) {}

    /**
     * @param  array<string, mixed>  $semanticQuery  the SemanticQuery DTO serialized to an array
     */
    public function handle(
        Dashboard $dashboard,
        SemanticModel $model,
        string $name,
        string $chartType,
        array $semanticQuery,
    ): Chart {
        $type = $this->chartTypes->get($chartType);

        $measureCount = count($semanticQuery['measures'] ?? []);
        $dimensionCount = count($semanticQuery['dimensions'] ?? []);
        $hasTimeDimension = isset($semanticQuery['time_dimension']) || isset($semanticQuery['timeDimension']);

        $errors = $type->validateShape($measureCount, $dimensionCount, $hasTimeDimension);

        if ($errors !== []) {
            throw new SemanticModelValidationException($errors);
        }

        $chart = Chart::query()->create([
            'dashboard_id' => $dashboard->id,
            'semantic_model_id' => $model->id,
            'name' => $name,
            'chart_type' => $chartType,
        ]);

        $chart->chartQuery()->create(['semantic_query' => $semanticQuery]);

        return $chart->fresh() ?? $chart;
    }
}
