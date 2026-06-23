<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QueryRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string|null $semantic_provider_id
 * @property string|null $dashboard_id
 * @property string|null $chart_id
 * @property string|null $provider_type
 * @property string|null $model_name
 * @property string $status
 * @property int|null $duration_ms
 * @property bool $cache_hit
 * @property string|null $cache_key
 * @property string|null $external_account_id
 * @property array<string, mixed>|null $query_shape
 * @property string|null $error_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'organization_id',
    'semantic_provider_id',
    'dashboard_id',
    'chart_id',
    'provider_type',
    'model_name',
    'status',
    'duration_ms',
    'cache_hit',
    'cache_key',
    'external_account_id',
    'query_shape',
    'error_message',
])]
class QueryRun extends Model
{
    /** @use HasFactory<QueryRunFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_query_runs';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_ms' => 'integer',
            'cache_hit' => 'boolean',
            'query_shape' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<SemanticProvider, $this>
     */
    public function semanticProvider(): BelongsTo
    {
        return $this->belongsTo(SemanticProvider::class);
    }

    /**
     * @return BelongsTo<Dashboard, $this>
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class);
    }
}
