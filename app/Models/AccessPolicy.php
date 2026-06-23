<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AccessPolicyFactory;
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
 * @property array<string, mixed> $rules
 * @property bool $is_required
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'semantic_model_id',
    'name',
    'rules',
    'is_required',
])]
class AccessPolicy extends Model
{
    /** @use HasFactory<AccessPolicyFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_access_policies';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'is_required' => 'boolean',
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
