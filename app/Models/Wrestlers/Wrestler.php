<?php

declare(strict_types=1);

namespace App\Models\Wrestlers;

use App\Builders\Roster\WrestlerBuilder;
use App\Casts\HeightCast;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\CanBeManaged;
use App\Models\Concerns\CanJoinStables;
use App\Models\Concerns\CanJoinTagTeams;
use App\Models\Concerns\CanWinTitles;
use App\Models\Concerns\HasEnumStatus;
use App\Models\Concerns\IsBookableCompetitor;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Concerns\ProvidesDisplayName;
use App\Models\Concerns\ValidatesEmployment;
use App\Models\Concerns\ValidatesIndividualDeletion;
use App\Models\Concerns\ValidatesIndividualInjury;
use App\Models\Concerns\ValidatesIndividualRestoration;
use App\Models\Concerns\ValidatesIndividualRetirement;
use App\Models\Concerns\ValidatesIndividualSuspension;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\CanBeATagTeamMember;
use App\Models\Contracts\CanBeChampion;
use App\Models\Contracts\Employable;
use App\Models\Contracts\HasDisplayName;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Suspension;
use App\Models\Managers\Manager;
use App\Models\Matches\EventMatch;
use App\Models\Stables\Stable;
use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Titles\TitleChampionship;
use App\ValueObjects\Height;
use Database\Factories\Wrestlers\WrestlerFactory;
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
 * @mixin \Eloquent
 *
 * @implements CanBeChampion<TitleChampionship>
 * @implements CanBeAStableMember<StableWrestler, static>
 * @implements CanBeATagTeamMember<TagTeamWrestler, static>
 * @implements Employable<static>
 * @implements Injurable<static>
 * @implements Manageable<WrestlerManager, static>
 * @implements Retirable<WrestlerRetirement, static>
 * @implements Suspendable<static>
 *
 * @property int $id
 * @property string $name
 * @property Height $height
 * @property int $weight
 * @property string $hometown
 * @property string|null $signature_move
 * @property EmploymentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Employment|null $currentEmployment
 * @property-read Employment|null $firstEmployment
 * @property-read Employment|null $futureEmployment
 * @property-read Employment|null $previousEmployment
 * @property-read Collection<int, Employment> $employments
 * @property-read Collection<int, Employment> $previousEmployments
 * @property-read Injury|null $currentInjury
 * @property-read Injury|null $previousInjury
 * @property-read Collection<int, Injury> $injuries
 * @property-read Collection<int, Injury> $previousInjuries
 * @property-read WrestlerRetirement|null $currentRetirement
 * @property-read WrestlerRetirement|null $previousRetirement
 * @property-read Collection<int, WrestlerRetirement> $retirements
 * @property-read Collection<int, WrestlerRetirement> $previousRetirements
 * @property-read Suspension|null $currentSuspension
 * @property-read Suspension|null $previousSuspension
 * @property-read Collection<int, Suspension> $suspensions
 * @property-read Collection<int, Suspension> $previousSuspensions
 * @property-read Stable|null $currentStable
 * @property-read Collection<int, Manager> $managers
 * @property-read Collection<int, Manager> $currentManagers
 * @property-read Collection<int, Manager> $previousManagers
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read TagTeam|null $currentTagTeam
 * @property-read TagTeam|null $previousTagTeam
 * @property-read Collection<int, TagTeam> $previousTagTeams
 * @property-read Collection<int, Stable> $stables
 * @property-read Collection<int, Stable> $previousStables
 * @property-read Collection<int, EventMatch> $matches
 * @property-read Collection<int, EventMatch> $previousMatches
 * @property-read TitleChampionship|null $currentChampionship
 * @property-read Collection<int, TitleChampionship> $titleChampionships
 * @property-read Collection<int, TitleChampionship> $currentChampionships
 * @property-read Collection<int, TitleChampionship> $previousTitleChampionships
 *
 * @method string getNameLabel()
 */
#[Fillable('name', 'height', 'weight', 'hometown', 'signature_move')]
#[Appends('status')]
#[UseFactory(WrestlerFactory::class)]
#[UseEloquentBuilder(WrestlerBuilder::class)]
class Wrestler extends Model implements Bookable, CanBeAStableMember, CanBeATagTeamMember, CanBeChampion, Employable, HasDisplayName, Injurable, Manageable, Retirable, Suspendable
{
    use BelongsToUser;

    /** @use CanBeManaged<WrestlerManager, static> */
    use CanBeManaged;

    /** @use CanJoinStables<StableWrestler, static> */
    use CanJoinStables;

    /** @use CanJoinTagTeams<TagTeamWrestler, static> */
    use CanJoinTagTeams;

    /** @use CanWinTitles<TitleChampionship> */
    use CanWinTitles;

    /** @use HasEnumStatus<EmploymentStatus> */
    use HasEnumStatus;

    /** @use HasFactory<WrestlerFactory> */
    use HasFactory;

    use IsBookableCompetitor;

    /** @use IsEmployable<static> */
    use IsEmployable;

    /** @use IsInjurable<static> */
    use IsInjurable;

    /** @use IsRetirable<WrestlerRetirement, static> */
    use IsRetirable;

    /** @use IsSuspendable<static> */
    use IsSuspendable;

    use ProvidesDisplayName;
    use SoftDeletes;
    use ValidatesEmployment;
    use ValidatesIndividualDeletion;
    use ValidatesIndividualInjury;
    use ValidatesIndividualRestoration;
    use ValidatesIndividualRetirement;
    use ValidatesIndividualSuspension;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'height' => HeightCast::class,
            // Status is computed attribute that already returns EmploymentStatus enum
        ];
    }

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
     * Check to see if the model is bookable.
     */
    public function isBookable(): bool
    {
        return ! ($this->isNotInEmployment() || $this->isSuspended() || $this->isInjured() || $this->hasFutureEmployment());
    }
}
