<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Embeds;

use App\Analytics\Semantic\DTOs\ProviderContext;
use RuntimeException;

final readonly class ResolveEmbedContext
{
    public function handle(string $token): ProviderContext
    {
        throw new RuntimeException('ResolveEmbedContext is implemented by a later milestone (M6).');
    }
}
