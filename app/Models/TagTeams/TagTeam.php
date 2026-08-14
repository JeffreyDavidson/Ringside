<?php

declare(strict_types=1);

namespace App\Models\TagTeams;

use App\Builders\Roster\TagTeamBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\CanBeManaged;
use App\Models\Concerns\HasChampionshipReigns;
use App\Models\Concerns\HasComputedEmploymentStatus;
use App\Models\Concerns\HasMatchParticipations;
use App\Models\Concerns\HasStableMemberships;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\CanBeChampion;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\SoftDeletable;
use App\Models\Contracts\Suspendable;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Retirement;
use App\Models\Lifecycle\Suspension;
use App\Models\Managers\Manager;
use App\Models\Matches\EventMatch;
use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\TagTeams\TagTeamFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @implements CanBeChampion<$this>
 * @implements CanBeAStableMember<StableTagTeam, $this>
 * @implements Employable<static>
 * @implements Manageable<TagTeamManager, static>
 * @implements Retirable<static>
 * @implements Suspendable<static>
 *
 * @property int $id
 * @property string $name
 * @property string|null $signature_move
 * @property EmploymentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read TagTeamWrestler|TagTeamManager|null $pivot
 * @property-read Employment|null $currentEmployment
 * @property-read Employment|null $firstEmployment
 * @property-read Employment|null $futureEmployment
 * @property-read Employment|null $previousEmployment
 * @property-read Collection<int, Employment> $employments
 * @property-read Collection<int, Employment> $previousEmployments
 * @property-read Retirement|null $currentRetirement
 * @property-read Retirement|null $previousRetirement
 * @property-read Collection<int, Retirement> $retirements
 * @property-read Collection<int, Retirement> $previousRetirements
 * @property-read Suspension|null $currentSuspension
 * @property-read Suspension|null $previousSuspension
 * @property-read Collection<int, Suspension> $suspensions
 * @property-read Collection<int, Suspension> $previousSuspensions
 * @property-read Collection<int, Wrestler> $wrestlers
 * @property-read Collection<int, Wrestler> $currentWrestlers
 * @property-read Collection<int, Wrestler> $previousWrestlers
 * @property-read Collection<int, Manager> $managers
 * @property-read Collection<int, Manager> $currentManagers
 * @property-read Collection<int, Manager> $previousManagers
 * @property-read Stable|null $currentStable
 * @property-read Collection<int, Stable> $stables
 * @property-read Collection<int, Stable> $previousStables
 * @property-read Collection<int, EventMatch> $matches
 * @property-read Collection<int, EventMatch> $previousMatches
 * @property-read Collection<int, TitleChampionship> $titleChampionships
 * @property-read Collection<int, TitleChampionship> $currentChampionships
 * @property-read Collection<int, TitleChampionship> $previousTitleChampionships
 *
 * @method static TagTeamBuilder<static>|TagTeam available()
 * @method static TagTeamBuilder<static>|TagTeam availableOn(\Carbon\Carbon $date)
 * @method static TagTeamBuilder<static>|TagTeam bookable()
 * @method static TagTeamBuilder<static>|TagTeam employed()
 * @method static \Database\Factories\TagTeams\TagTeamFactory factory($count = null, $state = [])
 * @method static TagTeamBuilder<static>|TagTeam futureEmployed()
 * @method static TagTeamBuilder<static>|TagTeam newModelQuery()
 * @method static TagTeamBuilder<static>|TagTeam newQuery()
 * @method static TagTeamBuilder<static>|TagTeam notBookedOn(\Carbon\Carbon $date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeam onlyTrashed()
 * @method static TagTeamBuilder<static>|TagTeam query()
 * @method static TagTeamBuilder<static>|TagTeam readyForBooking()
 * @method static TagTeamBuilder<static>|TagTeam released()
 * @method static TagTeamBuilder<static>|TagTeam retired()
 * @method static TagTeamBuilder<static>|TagTeam suspended()
 * @method static TagTeamBuilder<static>|TagTeam unavailable()
 * @method static TagTeamBuilder<static>|TagTeam unbookable()
 * @method static TagTeamBuilder<static>|TagTeam unemployed()
 * @method static TagTeamBuilder<static>|TagTeam withAvailableWrestlers()
 * @method static TagTeamBuilder<static>|TagTeam withMinimumWrestlers(int $count = 2)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeam withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeam withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable('name', 'signature_move')]
#[Appends('status')]
#[UseFactory(TagTeamFactory::class)]
#[UseEloquentBuilder(TagTeamBuilder::class)]
class TagTeam extends Model implements CanBeAStableMember, CanBeChampion, Employable, Manageable, Retirable, SoftDeletable, Suspendable
{
    /** @use CanBeManaged<TagTeamManager, static> */
    use CanBeManaged;

    use HasChampionshipReigns;
    use HasComputedEmploymentStatus;

    /** @use HasFactory<TagTeamFactory> */
    use HasFactory;

    use HasMatchParticipations;

    /** @use HasStableMemberships<StableTagTeam, $this> */
    use HasStableMemberships;

    /** @use IsEmployable<static> */
    use IsEmployable;

    /** @use IsRetirable<static> */
    use IsRetirable;

    /** @use IsSuspendable<static> */
    use IsSuspendable;

    use SoftDeletes;

    protected function managerAssignmentTable(): string
    {
        return (new TagTeamManager())->getTable();
    }

    protected function managerAssignmentPivotModel(): string
    {
        return TagTeamManager::class;
    }

    /** @return BelongsToMany<Wrestler, $this, TagTeamWrestler> */
    public function wrestlers(): BelongsToMany
    {
        return $this->belongsToMany(Wrestler::class, 'tag_teams_wrestlers', 'tag_team_id', 'wrestler_id')
            ->using(TagTeamWrestler::class)
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Wrestler, $this, TagTeamWrestler> */
    public function currentWrestlers(): BelongsToMany
    {
        return $this->wrestlers()->wherePivotNull('left_at');
    }

    /** @return BelongsToMany<Wrestler, $this, TagTeamWrestler> */
    public function previousWrestlers(): BelongsToMany
    {
        return $this->wrestlers()->wherePivotNotNull('left_at');
    }

    protected function stableMembershipTable(): string
    {
        return (new StableTagTeam())->getTable();
    }

    protected function stableMembershipForeignKey(): string
    {
        return 'tag_team_id';
    }

    protected function stableMembershipPivotModel(): string
    {
        return StableTagTeam::class;
    }

    /**
     * The number of the wrestlers allowed on a tag team.
     */
    public const int NUMBER_OF_WRESTLERS_ON_TEAM = 2;
}
