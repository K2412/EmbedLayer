<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AnalyticsJoinFactory;
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
 * @property string $left_table_alias
 * @property string $left_column
 * @property string $right_table
 * @property string $right_table_alias
 * @property string $right_column
 * @property string $type
 * @property string $relationship
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'semantic_model_id',
    'name',
    'left_table_alias',
    'left_column',
    'right_table',
    'right_table_alias',
    'right_column',
    'type',
    'relationship',
])]
class AnalyticsJoin extends Model
{
    /** @use HasFactory<AnalyticsJoinFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_joins';

    /**
     * @return BelongsTo<SemanticModel, $this>
     */
    public function semanticModel(): BelongsTo
    {
        return $this->belongsTo(SemanticModel::class);
    }
}
