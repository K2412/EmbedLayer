<?php

declare(strict_types=1);

namespace App\Http\Controllers\Embed;

use App\Analytics\Actions\Embeds\ResolveEmbedContext;
use App\Analytics\Embeds\EmbedTokenPayload;
use App\Analytics\Pipelines\QueryExecutionPipeline;
use App\Analytics\Semantic\DTOs\Filter;
use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Semantic\DTOs\TimeDimension;
use App\Analytics\Support\Exceptions\InvalidEmbedTokenException;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Http\Controllers\Controller;
use App\Models\Chart;
use App\Models\ChartQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Runs a chart's saved semantic query through the
 * {@see QueryExecutionPipeline}. The request body may add additional filters
 * but cannot override the stored model/measures/dimensions — those are
 * authoritative and live on the saved {@see Chart}/{@see ChartQuery}.
 */
final class ChartQueryController extends Controller
{
    public function __invoke(
        Request $request,
        QueryExecutionPipeline $pipeline,
        ResolveEmbedContext $resolver,
        string $chartId,
    ): JsonResponse {
        $payload = $this->payloadFor($request, $resolver);

        $chart = Chart::query()
            ->with(['chartQuery', 'dashboard', 'dashboard.embeds'])
            ->find($chartId);

        if ($chart === null) {
            return $this->forbidden('Chart not found.');
        }

        if (! in_array($chart->dashboard_id, $payload->allowedDashboardIds, true)) {
            return $this->forbidden('Chart is not in an allowed dashboard.');
        }

        $embed = $chart->dashboard->embeds
            ->firstWhere('id', $payload->embedId);

        if ($embed === null) {
            return $this->forbidden('Embed does not own this chart.');
        }

        if ($chart->chartQuery === null) {
            return response()->json(['error' => 'Chart has no semantic query.'], 422);
        }

        $context = new ProviderContext(
            organizationId: $payload->organizationId,
            projectId: $payload->projectId,
            externalAccountId: $payload->externalAccountId,
            embedId: $payload->embedId,
            claims: $payload->toClaims(),
        );

        $body = $request->json()->all();
        $overrideFilters = is_array($body) && isset($body['filters']) && is_array($body['filters'])
            ? $body['filters']
            : [];

        try {
            $query = $this->buildQuery($chart->chartQuery->semantic_query, $overrideFilters);
        } catch (SemanticModelValidationException $e) {
            return response()->json(['error' => $e->getMessage(), 'errors' => $e->errors], 422);
        }

        try {
            $result = $pipeline->execute($context, $query, $chart->dashboard, $chart);
        } catch (SemanticModelValidationException $e) {
            return response()->json(['error' => $e->getMessage(), 'errors' => $e->errors], 422);
        }

        return response()->json([
            'chart_id' => $chart->id,
            'result' => $result,
        ]);
    }

    /**
     * @param  array<string, mixed>  $stored
     * @param  array<int, mixed>  $overrideFilters
     */
    private function buildQuery(array $stored, array $overrideFilters): SemanticQuery
    {
        $model = isset($stored['model']) && is_string($stored['model']) ? $stored['model'] : '';
        if ($model === '') {
            throw new SemanticModelValidationException(['Stored chart query is missing a model.']);
        }

        $measures = $this->stringList($stored['measures'] ?? []);
        $dimensions = $this->stringList($stored['dimensions'] ?? []);

        $storedFilters = is_array($stored['filters'] ?? null) ? $stored['filters'] : [];
        $filters = [];

        foreach (array_values($storedFilters) as $entry) {
            $filter = $this->hydrateFilter($entry);
            if ($filter !== null) {
                $filters[] = $filter;
            }
        }

        foreach (array_values($overrideFilters) as $entry) {
            $filter = $this->hydrateFilter($entry);
            if ($filter !== null) {
                $filters[] = $filter;
            }
        }

        $timeDimension = null;
        $time = $stored['time_dimension'] ?? null;
        if (is_array($time) && isset($time['name'], $time['grain']) && is_string($time['name']) && is_string($time['grain'])) {
            $timeDimension = new TimeDimension(
                name: $time['name'],
                grain: $time['grain'],
            );
        }

        $orderBy = [];
        $rawOrder = $stored['order_by'] ?? [];
        if (is_array($rawOrder)) {
            foreach (array_values($rawOrder) as $entry) {
                if (is_array($entry) && isset($entry['field']) && is_string($entry['field'])) {
                    $direction = isset($entry['direction']) && is_string($entry['direction']) ? $entry['direction'] : 'asc';
                    $orderBy[] = ['field' => $entry['field'], 'direction' => $direction];
                }
            }
        }

        $limit = isset($stored['limit']) && is_int($stored['limit']) ? $stored['limit'] : null;
        $offset = isset($stored['offset']) && is_int($stored['offset']) ? $stored['offset'] : null;

        return new SemanticQuery(
            model: $model,
            measures: $measures,
            dimensions: $dimensions,
            timeDimension: $timeDimension,
            filters: $filters,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
        );
    }

    private function hydrateFilter(mixed $entry): ?Filter
    {
        if (! is_array($entry)) {
            return null;
        }

        $field = $entry['field'] ?? null;
        $operator = $entry['operator'] ?? '=';

        if (! is_string($field) || $field === '' || ! is_string($operator) || $operator === '') {
            return null;
        }

        $valueFromContext = isset($entry['value_from_context']) && is_string($entry['value_from_context'])
            ? $entry['value_from_context']
            : null;

        $value = $entry['value'] ?? null;

        return new Filter(
            field: $field,
            operator: $operator,
            value: $value,
            valueFromContext: $valueFromContext,
        );
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach (array_values($value) as $entry) {
            if (is_string($entry) && $entry !== '') {
                $out[] = $entry;
            }
        }

        return $out;
    }

    private function payloadFor(Request $request, ResolveEmbedContext $resolver): EmbedTokenPayload
    {
        $existing = $request->attributes->get('embed_token_payload');
        if ($existing instanceof EmbedTokenPayload) {
            return $existing;
        }

        $token = $request->bearerToken() ?? $request->query('token') ?? $request->cookie('embed_token');
        if (! is_string($token) || $token === '') {
            throw new InvalidEmbedTokenException('Embed token is missing.');
        }

        $context = $resolver->handle($token);

        return new EmbedTokenPayload(
            iss: 'embedlayer',
            sub: null,
            organizationId: $context->organizationId,
            projectId: $context->projectId,
            embedId: $context->embedId ?? '',
            externalAccountId: $context->externalAccountId,
            allowedDashboardIds: $this->stringList($context->claims['allowed_dashboard_ids'] ?? []),
            allowedModelNames: $this->stringList($context->claims['allowed_model_names'] ?? []),
            filters: [],
            theme: [],
        );
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json(['error' => $message], 403);
    }
}
