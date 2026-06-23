<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasUlids;

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasMany<AnalyticsProject, $this>
     */
    public function analyticsProjects(): HasMany
    {
        return $this->hasMany(AnalyticsProject::class);
    }

    /**
     * @return HasMany<DataSource, $this>
     */
    public function dataSources(): HasMany
    {
        return $this->hasMany(DataSource::class);
    }

    /**
     * @return HasMany<SemanticProvider, $this>
     */
    public function semanticProviders(): HasMany
    {
        return $this->hasMany(SemanticProvider::class);
    }

    /**
     * @return HasMany<Dashboard, $this>
     */
    public function dashboards(): HasMany
    {
        return $this->hasMany(Dashboard::class);
    }

    /**
     * @return HasMany<Embed, $this>
     */
    public function embeds(): HasMany
    {
        return $this->hasMany(Embed::class);
    }

    /**
     * @return HasMany<QueryRun, $this>
     */
    public function queryRuns(): HasMany
    {
        return $this->hasMany(QueryRun::class);
    }

    /**
     * @return HasMany<QueryCacheEntry, $this>
     */
    public function queryCacheEntries(): HasMany
    {
        return $this->hasMany(QueryCacheEntry::class);
    }
}
