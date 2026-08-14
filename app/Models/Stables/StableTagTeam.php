<?php

declare(strict_types=1);

namespace App\Models\Stables;

use App\Builders\Roster\MembershipPeriodBuilder;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $stable_id
 * @property int $tag_team_id
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Stable $stable
 * @property-read TagTeam $tagTeam
 *
 * @method static MembershipPeriodBuilder<static> current()
 * @method static MembershipPeriodBuilder<static> ended()
 * @method static MembershipPeriodBuilder<static> newModelQuery()
 * @method static MembershipPeriodBuilder<static> newQuery()
 * @method static MembershipPeriodBuilder<static> query()
 *
 * @mixin \Eloquent
 */
#[Fillable('stable_id', 'tag_team_id', 'joined_at', 'left_at')]
#[UseEloquentBuilder(MembershipPeriodBuilder::class)]
class StableTagTeam extends Pivot
{
    /** @var string */
    protected $table = 'stables_tag_teams';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Stable, $this> */
    public function stable(): BelongsTo
    {
        return $this->belongsTo(Stable::class);
    }

    /** @return BelongsTo<TagTeam, $this> */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }
}
