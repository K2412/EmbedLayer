<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Analytics\Embeds\EmbedTokenManager;
use App\Analytics\Support\Exceptions\InvalidEmbedTokenException;
use App\Models\EmbedDomain;
use Closure;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Enforces both the global `embedlayer.allowed_origins` allowlist and the
 * per-embed {@see EmbedDomain} list (Plan §17.4). Origin/Referer is checked
 * exact-match against the embed-specific allow list before the request can
 * touch any analytics data. Also handles CORS preflight and adds the response
 * headers the runtime needs to talk back.
 *
 * Aliased as `embed.origin` in {@see Middleware}.
 */
final readonly class EnforceEmbedOrigin
{
    public function __construct(private EmbedTokenManager $tokens) {}

    public function handle(Request $request, Closure $next): HttpResponse
    {
        $origin = $this->resolveOrigin($request);

        if ($request->getMethod() === 'OPTIONS') {
            return $this->applyCorsHeaders(new Response('', 204), $origin);
        }

        $token = $this->extractToken($request);

        if ($token === null) {
            return $this->reject(401, 'Embed token is missing.');
        }

        try {
            $payload = $this->tokens->decode($token);
        } catch (InvalidEmbedTokenException $e) {
            return $this->reject(401, 'Embed token is invalid: '.$e->getMessage());
        }

        if ($origin === null) {
            return $this->reject(403, 'Embed request is missing an Origin or Referer header.');
        }

        $host = $this->extractHost($origin);

        if ($host === null) {
            return $this->reject(403, 'Embed request Origin is malformed.');
        }

        $globalAllowed = $this->globalAllowedHosts();

        if ($globalAllowed !== [] && ! in_array($host, $globalAllowed, true)) {
            return $this->reject(403, 'Embed request Origin is not globally allowed.');
        }

        $embedHosts = EmbedDomain::query()
            ->where('embed_id', $payload->embedId)
            ->pluck('host')
            ->all();

        if (! in_array($host, $embedHosts, true)) {
            return $this->reject(403, 'Embed request Origin is not allowed for this embed.');
        }

        $request->attributes->set('embed_token_payload', $payload);
        $request->attributes->set('embed_token_raw', $token);

        $response = $next($request);

        if (! $response instanceof HttpResponse) {
            $response = new Response((string) $response);
        }

        return $this->applyCorsHeaders($response, $origin);
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

    private function resolveOrigin(Request $request): ?string
    {
        $origin = $request->header('Origin');
        if (is_string($origin) && $origin !== '') {
            return $origin;
        }

        $referer = $request->header('Referer');
        if (is_string($referer) && $referer !== '') {
            return $referer;
        }

        return null;
    }

    private function extractHost(string $origin): ?string
    {
        $host = parse_url($origin, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return strtolower($host);
    }

    /**
     * @return list<string>
     */
    private function globalAllowedHosts(): array
    {
        $raw = config('embedlayer.allowed_origins', []);

        if (! is_array($raw)) {
            return [];
        }

        $hosts = [];
        foreach ($raw as $entry) {
            if (! is_string($entry) || $entry === '') {
                continue;
            }

            if (str_contains($entry, '://')) {
                $host = parse_url($entry, PHP_URL_HOST);
                if (is_string($host) && $host !== '') {
                    $hosts[] = strtolower($host);
                }

                continue;
            }

            $hosts[] = strtolower($entry);
        }

        return $hosts;
    }

    private function applyCorsHeaders(HttpResponse $response, ?string $origin): HttpResponse
    {
        $response->headers->set('Access-Control-Allow-Origin', $origin ?? '*');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Vary', 'Origin');

        return $response;
    }

    private function reject(int $status, string $message): Response
    {
        return new Response(
            content: (string) json_encode(['error' => $message]),
            status: $status,
            headers: ['Content-Type' => 'application/json'],
        );
    }
}
