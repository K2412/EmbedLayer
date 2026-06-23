<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChartLayoutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $chart_id
 * @property string|null $dashboard_tab_id
 * @property int $grid_x
 * @property int $grid_y
 * @property int $grid_w
 * @property int $grid_h
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'chart_id',
    'dashboard_tab_id',
    'grid_x',
    'grid_y',
    'grid_w',
    'grid_h',
])]
class ChartLayout extends Model
{
    /** @use HasFactory<ChartLayoutFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_chart_layouts';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grid_x' => 'integer',
            'grid_y' => 'integer',
            'grid_w' => 'integer',
            'grid_h' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class);
    }

    /**
     * @return BelongsTo<DashboardTab, $this>
     */
    public function dashboardTab(): BelongsTo
    {
        return $this->belongsTo(DashboardTab::class);
    }
}
