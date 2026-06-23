<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EmbedFactory;
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
 * @property string $dashboard_id
 * @property string $name
 * @property int $default_ttl_seconds
 * @property array<string, mixed>|null $theme
 * @property array<string, mixed>|null $default_filters
 * @property bool $is_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'organization_id',
    'dashboard_id',
    'name',
    'default_ttl_seconds',
    'theme',
    'default_filters',
    'is_enabled',
])]
class Embed extends Model
{
    /** @use HasFactory<EmbedFactory> */
    use HasFactory, HasUlids;

    protected $table = 'analytics_embeds';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_ttl_seconds' => 'integer',
            'theme' => 'array',
            'default_filters' => 'array',
            'is_enabled' => 'boolean',
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
     * @return BelongsTo<Dashboard, $this>
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * @return HasMany<EmbedDomain, $this>
     */
    public function embedDomains(): HasMany
    {
        return $this->hasMany(EmbedDomain::class);
    }

    /**
     * @return HasMany<EmbedToken, $this>
     */
    public function embedTokens(): HasMany
    {
        return $this->hasMany(EmbedToken::class);
    }
}
