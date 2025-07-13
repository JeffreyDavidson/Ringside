<?php

declare(strict_types=1);

namespace App\Models\Stables;

use App\Builders\Roster\StableBuilder;
use App\Database\Query\Concerns\HasStatusScopes;
use App\Enums\Stables\StableStatus;
use App\Models\Concerns\HasActivityPeriods;
use App\Models\Concerns\HasMembers;
use App\Models\Concerns\HasStatusHistory;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\ValidatesActivation;
use App\Models\Concerns\ValidatesRetirement;
use App\Models\Contracts\Debutable;
use App\Models\Contracts\HasActivityPeriods as HasActivityPeriodsContract;
use App\Models\Contracts\Retirable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Stables\StableFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Tests\Unit\Models\Stables\StableTest;

/**
 * @implements Debutable<StableStatusChange, static>
 * @implements Retirable<StableRetirement, static>
 *
 * @property int $id
 * @property string $name
 * @property StableStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read StableStatusChange|null $debutStatusChange
 * @property-read StableStatusChange|null $latestStatusChange
 * @property-read Collection<int, StableStatusChange> $statusChanges
 * @property-read StableMember|null $pivot
 * @property-read StableRetirement|null $currentRetirement
 * @property-read StableRetirement|null $previousRetirement
 * @property-read Collection<int, StableRetirement> $retirements
 * @property-read Collection<int, StableRetirement> $previousRetirements
 * t
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, TagTeam> $currentTagTeams
 * @property-read Collection<int, TagTeam> $previousTagTeams
 * @property-read Collection<int, Wrestler> $wrestlers
 * @property-read Collection<int, Wrestler> $currentWrestlers
 * @property-read Collection<int, Wrestler> $previousWrestlers
 * @property-read Collection<int, StableActivityPeriod> $activations
 * @property-read Collection<int, StableActivityPeriod> $activityPeriods
 * @property-read StableActivityPeriod|null $currentActivityPeriod
 * @property-read StableActivityPeriod|null $firstActivityPeriod
 * @property-read StableActivityPeriod|null $futureActivityPeriod
 * @property-read StableActivityPeriod|null $previousActivityPeriod
 * @property-read Collection<int, StableActivityPeriod> $previousActivityPeriods
 *
 * @method static StableBuilder<static>|Stable activatedAfter(\Carbon\Carbon $date)
 * @method static StableBuilder<static>|Stable activatedBefore(\Carbon\Carbon $date)
 * @method static StableBuilder<static>|Stable active()
 * @method static StableBuilder<static>|Stable activeDuring(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static StableBuilder<static>|Stable activelyManaged()
 * @method static StableBuilder<static>|Stable available()
 * @method static StableBuilder<static>|Stable availableForReunion()
 * @method static StableBuilder<static>|Stable availableForStorylines()
 * @method static StableBuilder<static>|Stable belowMinimumMembers()
 * @method static StableBuilder<static>|Stable currentlyActive()
 * @method static StableBuilder<static>|Stable currentlyInactive()
 * @method static StableBuilder<static>|Stable deactivatedAfter(\Carbon\Carbon $date)
 * @method static StableBuilder<static>|Stable disbanded()
 * @method static StableBuilder<static>|Stable established()
 * @method static \Database\Factories\Stables\StableFactory factory($count = null, $state = [])
 * @method static StableBuilder<static>|Stable inactive()
 * @method static StableBuilder<static>|Stable neverActivated()
 * @method static StableBuilder<static>|Stable newModelQuery()
 * @method static StableBuilder<static>|Stable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stable onlyTrashed()
 * @method static StableBuilder<static>|Stable query()
 * @method static StableBuilder<static>|Stable retired()
 * @method static StableBuilder<static>|Stable unactivated()
 * @method static StableBuilder<static>|Stable unavailable()
 * @method static StableBuilder<static>|Stable unestablished()
 * @method static StableBuilder<static>|Stable withAvailableMembers()
 * @method static StableBuilder<static>|Stable withFutureActivation()
 * @method static StableBuilder<static>|Stable withFutureEstablishment()
 * @method static StableBuilder<static>|Stable withMemberCount(int $min, ?int $max = null)
 * @method static StableBuilder<static>|Stable withMinimumMembers()
 * @method static StableBuilder<static>|Stable withMultiplePeriods(int $minimumPeriods = 2)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stable withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stable withoutTrashed()
 *
 * @mixin \Eloquent
 *
 * @see StableTest
 */
#[UseFactory(StableFactory::class)]
#[UseEloquentBuilder(StableBuilder::class)]
class Stable extends Model implements Debutable, HasActivityPeriodsContract, Retirable
{
    /** @use HasActivityPeriods<StableActivityPeriod, static> */
    use HasActivityPeriods {
        HasActivityPeriods::isCurrentlyActive insteadof HasStatusHistory;
        HasActivityPeriods::isNotCurrentlyActive insteadof HasStatusHistory;
        HasActivityPeriods::isUnactivated insteadof HasStatusHistory;
        HasActivityPeriods::isInactive insteadof HasStatusHistory;
    }

    use HasFactory;
    use HasMembers;

    /** @use HasStatusHistory<StableStatusChange, static> */
    use HasStatusHistory;

    use HasStatusScopes;

    /** @use IsRetirable<StableRetirement, static> */
    use IsRetirable;

    use SoftDeletes;
    use ValidatesActivation;
    use ValidatesRetirement;

    /**
     * The minimum number of members allowed on a tag team.
     */
    public const int MIN_MEMBERS_COUNT = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StableStatus::class,
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => StableStatus::Unformed->value,
    ];
}
