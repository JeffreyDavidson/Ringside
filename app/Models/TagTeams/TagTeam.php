<?php

declare(strict_types=1);

namespace App\Models\TagTeams;

use App\Builders\TagTeamBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\CanBeManaged;
use App\Models\Concerns\CanJoinStables;
use App\Models\Concerns\CanWinTitles;
use App\Models\Concerns\HasMatches;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Concerns\ProvidesTagTeamWrestlers;
use App\Models\Concerns\ValidatesEmployment;
use App\Models\Concerns\ValidatesRetirement;
use App\Models\Concerns\ValidatesSuspension;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\CanBeChampion;
use App\Models\Contracts\Employable;
use App\Models\Contracts\HasTagTeamWrestlers;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Managers\Manager;
use App\Models\Matches\EventMatch;
use App\Models\Matches\EventMatchCompetitor;
use App\Models\Stables\Stable;
use App\Models\Stables\StableMember;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\TagTeams\TagTeamFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @implements Bookable<EventMatchCompetitor>
 * @implements CanBeChampion<TitleChampionship>
 * @implements CanBeAStableMember<StableMember, static>
 * @implements Employable<TagTeamEmployment, static>
 * @implements HasTagTeamWrestlers<static, TagTeamWrestler>
 * @implements Manageable<TagTeamManager, static>
 * @implements Retirable<TagTeamRetirement, static>
 * @implements Suspendable<TagTeamSuspension, static>
 *
 * @property int $id
 * @property string $name
 * @property string|null $signature_move
 * @property EmploymentStatus $status
 * @property-read int $combined_weight
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read TagTeamWrestler|TagTeamManager|null $pivot
 * @property-read TagTeamEmployment|null $currentEmployment
 * @property-read TagTeamEmployment|null $firstEmployment
 * @property-read TagTeamEmployment|null $futureEmployment
 * @property-read TagTeamEmployment|null $previousEmployment
 * @property-read Collection<int, TagTeamEmployment> $employments
 * @property-read Collection<int, TagTeamEmployment> $previousEmployments
 * @property-read TagTeamRetirement|null $currentRetirement
 * @property-read TagTeamRetirement|null $previousRetirement
 * @property-read Collection<int, TagTeamRetirement> $retirements
 * @property-read Collection<int, TagTeamRetirement> $previousRetirements
 * @property-read TagTeamSuspension|null $currentSuspension
 * @property-read TagTeamSuspension|null $previousSuspension
 * @property-read Collection<int, TagTeamSuspension> $suspensions
 * @property-read Collection<int, TagTeamSuspension> $previousSuspensions
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
#[UseFactory(TagTeamFactory::class)]
#[UseEloquentBuilder(TagTeamBuilder::class)]
class TagTeam extends Model implements Bookable, CanBeAStableMember, CanBeChampion, Employable, HasTagTeamWrestlers, Manageable, Retirable, Suspendable
{
    /** @use CanBeManaged<TagTeamManager, static> */
    use CanBeManaged;

    /** @use CanJoinStables<StableMember, static> */
    use CanJoinStables;

    /** @use CanWinTitles<TitleChampionship> */
    use CanWinTitles;

    use HasFactory;

    /** @use HasMatches<EventMatchCompetitor> */
    use HasMatches;

    /** @use IsEmployable<TagTeamEmployment, static> */
    use IsEmployable;

    /** @use IsRetirable<TagTeamRetirement, static> */
    use IsRetirable;

    /** @use IsSuspendable<TagTeamSuspension, static> */
    use IsSuspendable;

    /** @use ProvidesTagTeamWrestlers<TagTeamWrestler> */
    use ProvidesTagTeamWrestlers;

    use SoftDeletes;
    use ValidatesEmployment;
    use ValidatesRetirement;
    use ValidatesSuspension;

    /**
     * The number of the wrestlers allowed on a tag team.
     */
    public const int NUMBER_OF_WRESTLERS_ON_TEAM = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'signature_move',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => EmploymentStatus::Unemployed->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmploymentStatus::class,
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
