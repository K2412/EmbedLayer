<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $dashboard_id
 * @property string|null $dashboard_tab_id
 * @property string $semantic_model_id
 * @property string $name
 * @property string|null $description
 * @property string $chart_type
 * @property array<string, mixed>|null $options
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'dashboard_id',
    'dashboard_tab_id',
    'semantic_model_id',
    'name',
    'description',
    'chart_type',
    'options',
])]
class Chart extends Model
{
    /** @use HasFactory<ChartFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_charts';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Dashboard, $this>
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * @return BelongsTo<DashboardTab, $this>
     */
    public function dashboardTab(): BelongsTo
    {
        return $this->belongsTo(DashboardTab::class);
    }

    /**
     * @return BelongsTo<SemanticModel, $this>
     */
    public function semanticModel(): BelongsTo
    {
        return $this->belongsTo(SemanticModel::class);
    }

    /**
     * @return HasOne<ChartQuery, $this>
     */
    public function chartQuery(): HasOne
    {
        return $this->hasOne(ChartQuery::class);
    }

    /**
     * @return HasOne<ChartLayout, $this>
     */
    public function chartLayout(): HasOne
    {
        return $this->hasOne(ChartLayout::class);
    }
}
