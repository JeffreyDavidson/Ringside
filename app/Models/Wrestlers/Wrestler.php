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
use App\Models\Concerns\ValidatesInjury;
use App\Models\Concerns\ValidatesRestoration;
use App\Models\Concerns\ValidatesRetirement;
use App\Models\Concerns\ValidatesSuspension;
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
use App\Models\Managers\Manager;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Stables\Stable;
use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Titles\TitleChampionship;
use App\ValueObjects\Height;
use Database\Factories\Wrestlers\WrestlerFactory;
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
 * @implements Bookable<MatchCompetitor>
 * @implements CanBeChampion<TitleChampionship>
 * @implements CanBeAStableMember<StableWrestler, static>
 * @implements CanBeATagTeamMember<TagTeamWrestler, static>
 * @implements Employable<WrestlerEmployment, static>
 * @implements Injurable<WrestlerInjury, static>
 * @implements Manageable<WrestlerManager, static>
 * @implements Retirable<WrestlerRetirement, static>
 * @implements Suspendable<WrestlerSuspension, static>
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
 * @property-read WrestlerEmployment|null $currentEmployment
 * @property-read WrestlerEmployment|null $firstEmployment
 * @property-read WrestlerEmployment|null $futureEmployment
 * @property-read WrestlerEmployment|null $previousEmployment
 * @property-read Collection<int, WrestlerEmployment> $employments
 * @property-read Collection<int, WrestlerEmployment> $previousEmployments
 * @property-read WrestlerInjury|null $currentInjury
 * @property-read WrestlerInjury|null $previousInjury
 * @property-read Collection<int, WrestlerInjury> $injuries
 * @property-read Collection<int, WrestlerInjury> $previousInjuries
 * @property-read WrestlerRetirement|null $currentRetirement
 * @property-read WrestlerRetirement|null $previousRetirement
 * @property-read Collection<int, WrestlerRetirement> $retirements
 * @property-read Collection<int, WrestlerRetirement> $previousRetirements
 * @property-read WrestlerSuspension|null $currentSuspension
 * @property-read WrestlerSuspension|null $previousSuspension
 * @property-read Collection<int, WrestlerSuspension> $suspensions
 * @property-read Collection<int, WrestlerSuspension> $previousSuspensions
 * @property-read Stable|null $currentStable
 * @property-read Collection<int, Manager> $managers
 * @property-read Collection<int, Manager> $currentManagers
 * @property-read Collection<int, Manager> $previousManagers
 * @property-read Collection<int, TagTeam> $tagTeams
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
#[UseFactory(WrestlerFactory::class)]
#[UseEloquentBuilder(WrestlerBuilder::class)]
class Wrestler extends Model implements Bookable, CanBeAStableMember, CanBeATagTeamMember, CanBeChampion, Employable, HasDisplayName, Injurable, Manageable, Retirable, Suspendable
{
    /** @use BelongsToUser */
    use BelongsToUser;

    /** @use CanBeManaged<WrestlerManager, static> */
    use CanBeManaged;

    /** @use CanJoinStables<StableWrestler, static> */
    use CanJoinStables;

    /** @use CanJoinTagTeams<TagTeamWrestler, static> */
    use CanJoinTagTeams;

    /** @use CanWinTitles<TitleChampionship> */
    use CanWinTitles;

    use HasEnumStatus;
    use HasFactory;
    use IsBookableCompetitor;

    /** @use IsEmployable<WrestlerEmployment, static> */
    use IsEmployable;

    /** @use IsInjurable<WrestlerInjury, static> */
    use IsInjurable;

    /** @use IsRetirable<WrestlerRetirement, static> */
    use IsRetirable;

    /** @use IsSuspendable<WrestlerSuspension, static> */
    use IsSuspendable;

    use ProvidesDisplayName;
    use SoftDeletes;
    use ValidatesEmployment;
    use ValidatesInjury;
    use ValidatesRestoration;
    use ValidatesRetirement;
    use ValidatesSuspension;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'height',
        'weight',
        'hometown',
        'signature_move',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
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
