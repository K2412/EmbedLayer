<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DashboardTabFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $dashboard_id
 * @property string $name
 * @property string $slug
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'dashboard_id',
    'name',
    'slug',
    'position',
])]
class DashboardTab extends Model
{
    /** @use HasFactory<DashboardTabFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_dashboard_tabs';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
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
     * @return HasMany<Chart, $this>
     */
    public function charts(): HasMany
    {
        return $this->hasMany(Chart::class);
    }

    /**
     * @return HasMany<ChartLayout, $this>
     */
    public function chartLayouts(): HasMany
    {
        return $this->hasMany(ChartLayout::class);
    }
}
