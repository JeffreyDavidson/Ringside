<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $tag_team_id
 * @property int $wrestler_id
 * @property \Illuminate\Support\Carbon $joined_at
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read TagTeam|null $tagTeam
 * @property-read Wrestler|null $partner
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamPartner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamPartner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamPartner query()
 *
 * @mixin \Eloquent
 */
final class TagTeamPartner extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tag_teams_wrestlers';

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
     * @return BelongsTo<TagTeam, $this>
     */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }

    /**
     * @return BelongsTo<Wrestler, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class);
    }
}
