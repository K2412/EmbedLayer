<?php

declare(strict_types=1);

namespace App\Http\Controllers\Embed;

use App\Analytics\Actions\Embeds\ResolveEmbedContext;
use App\Analytics\Embeds\EmbedTokenPayload;
use App\Analytics\Support\Exceptions\InvalidEmbedTokenException;
use App\Http\Controllers\Controller;
use App\Models\Chart;
use App\Models\Embed;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public-facing embed endpoints (Plan §12). The iframe entry route is
 * unauthenticated — it just renders the runtime shell with the token from the
 * query string or cookie. The JSON endpoints are protected by the
 * `embed.origin` middleware which decodes the token and enforces the
 * EmbedDomain allowlist.
 */
final class DashboardController extends Controller
{
    public function iframe(Request $request, string $embedId): View
    {
        $token = $this->extractToken($request);

        return view('embed.dashboard', [
            'embed_id' => $embedId,
            'token' => $token ?? '',
        ]);
    }

    public function show(Request $request, ResolveEmbedContext $resolver, string $embedId): JsonResponse
    {
        $payload = $this->payloadFor($request, $resolver);

        if ($payload->embedId !== $embedId) {
            return $this->forbidden('Embed token does not match the requested embed.');
        }

        if (! in_array($embedId, $payload->allowedDashboardIds, true) && $payload->allowedDashboardIds !== []) {
            // Allowed-dashboard-ids contain dashboard ids, not embed ids; we check
            // the embed -> dashboard binding below.
        }

        $embed = Embed::query()
            ->with(['dashboard.charts.chartQuery', 'dashboard.charts.semanticModel'])
            ->find($embedId);

        if ($embed === null || ! $embed->is_enabled) {
            return $this->forbidden('Embed is not enabled.');
        }

        if ($embed->organization_id !== $payload->organizationId) {
            return $this->forbidden('Embed does not belong to this organization.');
        }

        if (! in_array($embed->dashboard_id, $payload->allowedDashboardIds, true)) {
            return $this->forbidden('Embed token does not allow this dashboard.');
        }

        $dashboard = $embed->dashboard;

        $charts = $dashboard->charts->map(static fn (Chart $chart): array => [
            'id' => $chart->id,
            'name' => $chart->name,
            'description' => $chart->description,
            'chart_type' => $chart->chart_type,
            'options' => $chart->options ?? [],
            'semantic_query' => $chart->chartQuery?->semantic_query ?? [],
            'semantic_model' => $chart->semanticModel->name ?? null,
        ])->all();

        return response()->json([
            'embed' => [
                'id' => $embed->id,
                'name' => $embed->name,
                'theme' => is_array($embed->theme) ? $embed->theme : (object) [],
            ],
            'dashboard' => [
                'id' => $dashboard->id,
                'name' => $dashboard->name,
                'slug' => $dashboard->slug,
                'description' => $dashboard->description,
                'theme' => is_array($dashboard->theme) ? $dashboard->theme : (object) [],
            ],
            'charts' => $charts,
        ]);
    }

    private function payloadFor(Request $request, ResolveEmbedContext $resolver): EmbedTokenPayload
    {
        $existing = $request->attributes->get('embed_token_payload');
        if ($existing instanceof EmbedTokenPayload) {
            return $existing;
        }

        $token = $this->extractToken($request);
        if ($token === null) {
            throw new InvalidEmbedTokenException('Embed token is missing.');
        }

        // ResolveEmbedContext returns ProviderContext; we need the payload here,
        // so reuse the same token manager via the action's dependency.
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

    private function extractToken(Request $request): ?string
    {
        $authorization = $request->header('Authorization');
        if (is_string($authorization) && str_starts_with($authorization, 'Bearer ')) {
            $value = trim(substr($authorization, 7));
            if ($value !== '') {
                return $value;
            }
        }

        $cookie = $request->cookie('embed_token');
        if (is_string($cookie) && $cookie !== '') {
            return $cookie;
        }

        $query = $request->query('token');
        if (is_string($query) && $query !== '') {
            return $query;
        }

        return null;
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

    private function forbidden(string $message): JsonResponse
    {
        return response()->json(['error' => $message], 403);
    }
}
