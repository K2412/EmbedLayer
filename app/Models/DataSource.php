<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DataSourceFactory;
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
 * @property string $name
 * @property string $driver
 * @property array<string, mixed> $encrypted_config
 * @property array<string, mixed>|null $capabilities
 * @property array<string, mixed>|null $last_introspected_schema
 * @property Carbon|null $last_tested_at
 * @property Carbon|null $last_introspected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'organization_id',
    'name',
    'driver',
    'encrypted_config',
    'capabilities',
    'last_introspected_schema',
    'last_tested_at',
    'last_introspected_at',
])]
class DataSource extends Model
{
    /** @use HasFactory<DataSourceFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_data_sources';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'encrypted_config' => 'array',
            'capabilities' => 'array',
            'last_introspected_schema' => 'array',
            'last_tested_at' => 'datetime',
            'last_introspected_at' => 'datetime',
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
     * @return HasMany<SemanticProvider, $this>
     */
    public function semanticProviders(): HasMany
    {
        return $this->hasMany(SemanticProvider::class);
    }
}
