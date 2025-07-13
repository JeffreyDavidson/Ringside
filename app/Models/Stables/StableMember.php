<?php

declare(strict_types=1);

namespace App\Models\Stables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Polymorphic pivot model for stable memberships.
 *
 * This model handles the many-to-many polymorphic relationship between
 * stables and their members (wrestlers, tag teams, managers). It tracks
 * when members join and leave stables through timestamp fields.
 *
 * @property int $id
 * @property int $stable_id
 * @property int $member_id
 * @property string $member_type
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Stable $stable
 * @property-read Model $member
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableMember query()
 *
 * @mixin \Eloquent
 */
class StableMember extends MorphPivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stables_members';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'stable_id',
        'member_id',
        'member_type',
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
     * Get the member (wrestler, tag team, manager, etc.) for this membership.
     *
     * @return MorphTo<Model, $this>
     */
    public function member(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determine if this membership is currently active.
     *
     * A membership is active if the member has not left the stable
     * (left_at is null).
     */
    public function isActive(): bool
    {
        return $this->left_at === null;
    }

    /**
     * Determine if this membership has ended.
     *
     * A membership has ended if the member has left the stable
     * (left_at is not null).
     */
    public function hasEnded(): bool
    {
        return $this->left_at !== null;
    }
}
