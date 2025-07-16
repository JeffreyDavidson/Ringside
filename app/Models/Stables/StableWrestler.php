<?php

declare(strict_types=1);

namespace App\Models\Stables;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * Pivot model for stable-wrestler relationships.
 *
 * This model handles the many-to-many relationship between
 * stables and wrestlers. It tracks when wrestlers join and 
 * leave stables through timestamp fields.
 *
 * @property int $id
 * @property int $stable_id
 * @property int $wrestler_id
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Stable $stable
 * @property-read Wrestler $wrestler
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableWrestler newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableWrestler newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableWrestler query()
 *
 * @mixin \Eloquent
 */
class StableWrestler extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stables_wrestlers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'stable_id',
        'wrestler_id',
        'joined_at',
        'left_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /**
     * Get the stable that this membership belongs to.
     *
     * @return BelongsTo<Stable, $this>
     */
    public function stable(): BelongsTo
    {
        return $this->belongsTo(Stable::class);
    }

    /**
     * Get the wrestler for this membership.
     *
     * @return BelongsTo<Wrestler, $this>
     */
    public function wrestler(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class);
    }

    /**
     * Determine if this membership is currently active.
     *
     * A membership is active if the wrestler has not left the stable
     * (left_at is null).
     */
    public function isActive(): bool
    {
        return $this->left_at === null;
    }

    /**
     * Determine if this membership has ended.
     *
     * A membership has ended if the wrestler has left the stable
     * (left_at is not null).
     */
    public function hasEnded(): bool
    {
        return $this->left_at !== null;
    }
}