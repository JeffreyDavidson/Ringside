<?php

declare(strict_types=1);

namespace App\Models\TagTeams;

use App\Builders\Roster\TagTeamBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\CanBeManaged;
use App\Models\Concerns\CanJoinStables;
use App\Models\Concerns\CanWinTitles;
use App\Models\Concerns\IsBookableCompetitor;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Concerns\ProvidesTagTeamWrestlers;
use App\Models\Concerns\ValidatesTagTeamDeletion;
use App\Models\Concerns\ValidatesTagTeamEmployment;
use App\Models\Concerns\ValidatesTagTeamRelease;
use App\Models\Concerns\ValidatesTagTeamRetirement;
use App\Models\Concerns\ValidatesTagTeamSuspension;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\CanBeChampion;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
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
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @implements CanBeChampion<TitleChampionship>
 * @implements CanBeAStableMember<StableTagTeam, static>
 * @implements Employable<static>
 * @implements Manageable<TagTeamManager, static>
 * @implements Retirable<static>
 * @implements Suspendable<static>
 *
 * @property int $id
 * @property string $name
 * @property string|null $signature_move
 * @property EmploymentStatus $status
 *
 * @property-read int $combined_weight
 *
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
 * @property-read TitleChampionship|null $currentChampionship
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
class TagTeam extends Model implements Bookable, CanBeAStableMember, CanBeChampion, Employable, Manageable, Retirable, Suspendable
{
    /** @use CanBeManaged<TagTeamManager, static> */
    use CanBeManaged;

    /** @use CanJoinStables<StableTagTeam, static> */
    use CanJoinStables;

    /** @use CanWinTitles<TitleChampionship> */
    use CanWinTitles;

    /** @use HasFactory<TagTeamFactory> */
    use HasFactory;

    use IsBookableCompetitor;

    /** @use IsEmployable<static> */
    use IsEmployable;

    /** @use IsRetirable<static> */
    use IsRetirable;

    /** @use IsSuspendable<static> */
    use IsSuspendable;

    /** @use ProvidesTagTeamWrestlers<TagTeamWrestler> */
    use ProvidesTagTeamWrestlers;

    use SoftDeletes;
    use ValidatesTagTeamDeletion;
    use ValidatesTagTeamEmployment;
    use ValidatesTagTeamRelease;
    use ValidatesTagTeamRetirement;
    use ValidatesTagTeamSuspension;

    /**
     * The number of the wrestlers allowed on a tag team.
     */
    public const int NUMBER_OF_WRESTLERS_ON_TEAM = 2;

    /**
     * Get the computed status attribute.
     *
     * Computes the employment status based on the tag team's current relationships:
     * - Retired: Has active retirement record
     * - Employed: Has active employment (started <= now)
     * - FutureEmployment: Has employment starting in future
     * - Released: Has previous employment but no current employment
     * - Unemployed: No employment history
     *
     * @return Attribute<EmploymentStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (): EmploymentStatus {
                // Priority: Retired > Employed > FutureEmployment > Released > Unemployed
                if ($this->isRetired()) {
                    return EmploymentStatus::Retired;
                }

                if ($this->currentEmployment) {
                    return EmploymentStatus::Employed;
                }

                if ($this->futureEmployment) {
                    return EmploymentStatus::FutureEmployment;
                }

                if ($this->previousEmployments()->exists()) {
                    return EmploymentStatus::Released;
                }

                return EmploymentStatus::Unemployed;
            }
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Status is computed attribute that already returns EmploymentStatus enum
        ];
    }

    /**
     * Check to see if the model is bookable.
     */
    public function isBookable(): bool
    {
        return $this->currentWrestlers->every(fn (Wrestler $wrestler) => $wrestler->isBookable());
    }

    /**
     * Check to see if the tag team is unbookable.
     */
    public function isUnbookable(): bool
    {
        return ! $this->currentWrestlers->every(fn (Wrestler $wrestler): bool => $wrestler->isBookable());
    }
}
