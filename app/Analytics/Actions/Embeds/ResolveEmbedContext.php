<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Embeds;

use App\Analytics\Embeds\EmbedTokenManager;
use App\Analytics\Semantic\DTOs\ProviderContext;

/**
 * Decodes an embed JWT and converts it into the {@see ProviderContext} that
 * the query pipeline understands. The full claim map is preserved so that
 * downstream pipes (AccessPolicyCompiler, FieldValidator) can pull
 * `value_from_context` references straight from the token.
 */
final readonly class ResolveEmbedContext
{
    public function __construct(private EmbedTokenManager $tokens) {}

    public function handle(string $token): ProviderContext
    {
        $payload = $this->tokens->decode($token);

        return new ProviderContext(
            organizationId: $payload->organizationId,
            projectId: $payload->projectId,
            externalAccountId: $payload->externalAccountId,
            embedId: $payload->embedId,
            claims: $payload->toClaims(),
        );
    }
}
