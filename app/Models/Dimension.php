<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DimensionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $semantic_model_id
 * @property string $name
 * @property string $label
 * @property string|null $description
 * @property string $type
 * @property string $column
 * @property string|null $table_alias
 * @property bool $is_filterable
 * @property bool $is_groupable
 * @property bool $is_public
 * @property array<int, string>|null $allowed_time_grains
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'semantic_model_id',
    'name',
    'label',
    'description',
    'type',
    'column',
    'table_alias',
    'is_filterable',
    'is_groupable',
    'is_public',
    'allowed_time_grains',
])]
class Dimension extends Model
{
    /** @use HasFactory<DimensionFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_dimensions';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_groupable' => 'boolean',
            'is_public' => 'boolean',
            'allowed_time_grains' => 'array',
        ];
    }

    /**
     * @return BelongsTo<SemanticModel, $this>
     */
    public function semanticModel(): BelongsTo
    {
        return $this->belongsTo(SemanticModel::class);
    }
}
