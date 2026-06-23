<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SemanticProviderFactory;
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
 * @property string $type
 * @property string|null $data_source_id
 * @property string|null $encrypted_config
 * @property array<string, mixed>|null $capabilities
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'organization_id',
    'name',
    'type',
    'data_source_id',
    'encrypted_config',
    'capabilities',
])]
class SemanticProvider extends Model
{
    /** @use HasFactory<SemanticProviderFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_semantic_providers';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'encrypted_config' => 'string',
            'capabilities' => 'array',
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
     * @return BelongsTo<DataSource, $this>
     */
    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    /**
     * @return HasMany<SemanticModel, $this>
     */
    public function semanticModels(): HasMany
    {
        return $this->hasMany(SemanticModel::class);
    }
}
