<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Embeds;

use App\Models\Embed;
use RuntimeException;

final readonly class GenerateEmbedToken
{
    /**
     * @param  array<string, mixed>  $claims
     */
    public function handle(Embed $embed, array $claims): string
    {
        throw new RuntimeException('GenerateEmbedToken is implemented by a later milestone (M6).');
    }
}
