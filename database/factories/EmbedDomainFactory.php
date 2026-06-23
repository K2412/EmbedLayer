<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Embed;
use App\Models\EmbedDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmbedDomain>
 */
class EmbedDomainFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'embed_id' => Embed::factory(),
            'host' => fake()->unique()->domainName(),
        ];
    }
}
