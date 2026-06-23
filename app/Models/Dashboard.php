<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DashboardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $analytics_project_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array<string, mixed>|null $theme
 * @property array<string, mixed>|null $default_filters
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'organization_id',
    'analytics_project_id',
    'name',
    'slug',
    'description',
    'theme',
    'default_filters',
    'is_published',
    'published_at',
])]
class Dashboard extends Model
{
    /** @use HasFactory<DashboardFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_dashboards';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'theme' => 'array',
            'default_filters' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
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
     * @return BelongsTo<AnalyticsProject, $this>
     */
    public function analyticsProject(): BelongsTo
    {
        return $this->belongsTo(AnalyticsProject::class);
    }

    /**
     * @return HasMany<DashboardTab, $this>
     */
    public function dashboardTabs(): HasMany
    {
        return $this->hasMany(DashboardTab::class);
    }

    /**
     * @return HasMany<Chart, $this>
     */
    public function charts(): HasMany
    {
        return $this->hasMany(Chart::class);
    }

    /**
     * @return HasMany<Embed, $this>
     */
    public function embeds(): HasMany
    {
        return $this->hasMany(Embed::class);
    }
}
