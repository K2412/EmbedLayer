<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MeasureFactory;
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
 * @property string|null $column
 * @property array<string, mixed>|null $expression
 * @property array<string, mixed>|null $filters
 * @property string|null $format
 * @property bool $is_public
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
    'expression',
    'filters',
    'format',
    'is_public',
])]
class Measure extends Model
{
    /** @use HasFactory<MeasureFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_measures';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expression' => 'array',
            'filters' => 'array',
            'is_public' => 'boolean',
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
