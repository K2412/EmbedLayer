<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SemanticModelFactory;
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
 * @property string $semantic_provider_id
 * @property string $name
 * @property string $label
 * @property string|null $description
 * @property string|null $base_table
 * @property string|null $base_table_alias
 * @property bool $is_enabled
 * @property int $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'organization_id',
    'semantic_provider_id',
    'name',
    'label',
    'description',
    'base_table',
    'base_table_alias',
    'is_enabled',
    'version',
])]
class SemanticModel extends Model
{
    /** @use HasFactory<SemanticModelFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_semantic_models';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'version' => 'integer',
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
     * @return HasMany<Measure, $this>
     */
    public function measures(): HasMany
    {
        return $this->hasMany(Measure::class);
    }

    /**
     * @return HasMany<Dimension, $this>
     */
    public function dimensions(): HasMany
    {
        return $this->hasMany(Dimension::class);
    }

    /**
     * @return HasMany<AnalyticsJoin, $this>
     */
    public function joins(): HasMany
    {
        return $this->hasMany(AnalyticsJoin::class, 'semantic_model_id');
    }

    /**
     * @return HasMany<AccessPolicy, $this>
     */
    public function accessPolicies(): HasMany
    {
        return $this->hasMany(AccessPolicy::class);
    }
}
