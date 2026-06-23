<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChartQueryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $chart_id
 * @property array<string, mixed> $semantic_query
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'chart_id',
    'semantic_query',
])]
class ChartQuery extends Model
{
    /** @use HasFactory<ChartQueryFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_chart_queries';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semantic_query' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class);
    }
}
