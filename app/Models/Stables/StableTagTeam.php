<?php

declare(strict_types=1);

namespace App\Models\Stables;

use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * Pivot model for stable-tag team relationships.
 *
 * This model handles the many-to-many relationship between
 * stables and tag teams. It tracks when tag teams join and 
 * leave stables through timestamp fields.
 *
 * @property int $id
 * @property int $stable_id
 * @property int $tag_team_id
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Stable $stable
 * @property-read TagTeam $tagTeam
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableTagTeam newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableTagTeam newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableTagTeam query()
 *
 * @mixin \Eloquent
 */
class StableTagTeam extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stables_tag_teams';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'stable_id',
        'tag_team_id',
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
     * Get the tag team for this membership.
     *
     * @return BelongsTo<TagTeam, $this>
     */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }

    /**
     * Determine if this membership is currently active.
     *
     * A membership is active if the tag team has not left the stable
     * (left_at is null).
     */
    public function isActive(): bool
    {
        return $this->left_at === null;
    }

    /**
     * Determine if this membership has ended.
     *
     * A membership has ended if the tag team has left the stable
     * (left_at is not null).
     */
    public function hasEnded(): bool
    {
        return $this->left_at !== null;
    }
}