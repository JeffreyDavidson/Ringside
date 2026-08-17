<?php

declare(strict_types=1);

namespace App\Models\Roster\TagTeams;

use App\Builders\Roster\TagTeamMembershipBuilder;
use App\Models\Roster\Wrestlers\Wrestler;
use Database\Factories\Roster\TagTeams\TagTeamWrestlerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
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
 *
 * @property-read TagTeam|null $tagTeam
 * @property-read Wrestler|null $wrestler
 *
 * @method static TagTeamMembershipBuilder<static> current()
 * @method static TagTeamMembershipBuilder<static> ended()
 * @method static TagTeamMembershipBuilder<static> excludingWrestlerId(int $wrestlerId)
 * @method static TagTeamMembershipBuilder<static> forTagTeamId(int $tagTeamId)
 * @method static TagTeamMembershipBuilder<static> forWrestlerId(int $wrestlerId)
 * @method static TagTeamMembershipBuilder<static> mostRecentlyJoinedFirst()
 * @method static TagTeamMembershipBuilder<static> newModelQuery()
 * @method static TagTeamMembershipBuilder<static> newQuery()
 * @method static TagTeamMembershipBuilder<static> overlappingPeriod(Carbon $periodStart, Carbon $periodEnd)
 * @method static TagTeamMembershipBuilder<static> query()
 *
 * @mixin \Eloquent
 */
#[Fillable('tag_team_id', 'wrestler_id', 'joined_at', 'left_at')]
#[UseEloquentBuilder(TagTeamMembershipBuilder::class)]
#[UseFactory(TagTeamWrestlerFactory::class)]
#[Table(name: 'tag_teams_wrestlers')]
class TagTeamWrestler extends Pivot
{
    /** @use HasFactory<TagTeamWrestlerFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<TagTeam, $this> */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }

    /** @return BelongsTo<Wrestler, $this> */
    public function wrestler(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class, 'wrestler_id');
    }
}
