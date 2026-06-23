<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EmbedDomainFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $embed_id
 * @property string $host
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'embed_id',
    'host',
])]
class EmbedDomain extends Model
{
    /** @use HasFactory<EmbedDomainFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_embed_domains';

    /**
     * @return BelongsTo<Embed, $this>
     */
    public function embed(): BelongsTo
    {
        return $this->belongsTo(Embed::class);
    }
}
