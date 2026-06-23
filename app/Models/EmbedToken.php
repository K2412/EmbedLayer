<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EmbedTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $embed_id
 * @property string $jti
 * @property string|null $external_account_id
 * @property string $payload_hash
 * @property Carbon $issued_at
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'embed_id',
    'jti',
    'external_account_id',
    'payload_hash',
    'issued_at',
    'expires_at',
    'revoked_at',
])]
class EmbedToken extends Model
{
    /** @use HasFactory<EmbedTokenFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_embed_tokens';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Embed, $this>
     */
    public function embed(): BelongsTo
    {
        return $this->belongsTo(Embed::class);
    }
}
