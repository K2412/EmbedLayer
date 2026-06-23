<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QueryCacheEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $cache_key
 * @property array<string, mixed> $result
 * @property array<string, mixed>|null $metadata
 * @property Carbon $expires_at
 * @property Carbon|null $last_accessed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'organization_id',
    'cache_key',
    'result',
    'metadata',
    'expires_at',
    'last_accessed_at',
])]
class QueryCacheEntry extends Model
{
    /** @use HasFactory<QueryCacheEntryFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_query_cache_entries';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result' => 'array',
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
