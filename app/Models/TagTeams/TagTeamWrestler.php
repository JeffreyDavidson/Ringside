<?php

declare(strict_types=1);

namespace App\Models\TagTeams;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tag_team_id
 * @property int $wrestler_id
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TagTeam|null $tagTeam
 * @property-read Wrestler|null $wrestler
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamWrestler newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamWrestler newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamWrestler query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(WrestlerFactory::class)]
class TagTeamWrestler extends Pivot
{
    use HasFactory;

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
     * Get the tag team associated with this tag team partner.
     *
     * @return BelongsTo<TagTeam, $this>
     */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }

    /**
     * Get the wrestler associated with this tag team relationship.
     *
     * @return BelongsTo<Wrestler, $this>
     */
    public function wrestler(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class, 'wrestler_id');
    }
}
