<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Embed;
use App\Models\EmbedToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmbedToken>
 */
class EmbedTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issuedAt = Carbon::now();

        return [
            'embed_id' => Embed::factory(),
            'jti' => (string) Str::ulid(),
            'external_account_id' => fake()->optional()->uuid(),
            'payload_hash' => hash('sha256', fake()->sha256()),
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt->copy()->addMinutes(5),
            'revoked_at' => null,
        ];
    }
}
