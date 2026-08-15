<?php

declare(strict_types=1);

namespace App\Models\Wrestlers;

use App\Builders\Roster\WrestlerBuilder;
use App\Casts\HeightCast;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\HasChampionshipReigns;
use App\Models\Concerns\HasComputedEmploymentStatus;
use App\Models\Concerns\HasMatchParticipations;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\CanBeChampion;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\SoftDeletable;
use App\Models\Contracts\Suspendable;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Retirement;
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @mixin \Eloquent
 *
 * @implements CanBeChampion<$this>
 * @implements CanBeAStableMember<StableWrestler, $this>
 * @implements Employable<static>
 * @implements Injurable<static>
 * @implements Manageable<WrestlerManager, $this>
 * @implements Retirable<static>
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
 * @property-read Retirement|null $currentRetirement
 * @property-read Retirement|null $previousRetirement
 * @property-read Collection<int, Retirement> $retirements
 * @property-read Collection<int, Retirement> $previousRetirements
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
 * @property-read Collection<int, TitleChampionship> $titleChampionships
 * @property-read Collection<int, TitleChampionship> $currentChampionships
 * @property-read Collection<int, TitleChampionship> $previousTitleChampionships
 */
#[Fillable('name', 'height', 'weight', 'hometown', 'signature_move')]
#[Appends('status')]
#[UseFactory(WrestlerFactory::class)]
#[UseEloquentBuilder(WrestlerBuilder::class)]
class Wrestler extends Model implements CanBeAStableMember, CanBeChampion, Employable, Injurable, Manageable, Retirable, SoftDeletable, Suspendable
{
    use HasChampionshipReigns;
    use HasComputedEmploymentStatus;

    /** @use HasFactory<WrestlerFactory> */
    use HasFactory;

    use HasMatchParticipations;

    /** @use IsEmployable<static> */
    use IsEmployable;

    /** @use IsInjurable<static> */
    use IsInjurable;

    /** @use IsRetirable<static> */
    use IsRetirable;

    /** @use IsSuspendable<static> */
    use IsSuspendable;

    use SoftDeletes;

    /** @return BelongsToMany<Manager, $this, WrestlerManager> */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class, (new WrestlerManager())->getTable())
            ->using(WrestlerManager::class)
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Manager, $this, WrestlerManager> */
    public function currentManagers(): BelongsToMany
    {
        return $this->managers()->wherePivotNull('fired_at');
    }

    /** @return BelongsToMany<Manager, $this, WrestlerManager> */
    public function previousManagers(): BelongsToMany
    {
        return $this->managers()->wherePivotNotNull('fired_at');
    }

    /** @return BelongsToMany<Stable, $this, StableWrestler> */
    public function stables(): BelongsToMany
    {
        return $this->belongsToMany(
            Stable::class,
            (new StableWrestler())->getTable(),
            'wrestler_id',
            'stable_id',
        )
            ->using(StableWrestler::class)
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    /** @return HasOneThrough<Stable, StableWrestler, $this> */
    public function currentStable(): HasOneThrough
    {
        return $this->hasOneThrough(
            Stable::class,
            StableWrestler::class,
            'wrestler_id',
            'id',
            'id',
            'stable_id',
        )->whereNull((new StableWrestler())->qualifyColumn('left_at'));
    }

    /** @return BelongsToMany<Stable, $this, StableWrestler> */
    public function previousStables(): BelongsToMany
    {
        return $this->stables()->wherePivotNotNull('left_at');
    }

    /**
     * @return BelongsToMany<TagTeam, $this, TagTeamWrestler>
     */
    public function tagTeams(): BelongsToMany
    {
        return $this->belongsToMany(TagTeam::class, 'tag_teams_wrestlers', 'wrestler_id', 'tag_team_id')
            ->withPivot(['joined_at', 'left_at'])
            ->using(TagTeamWrestler::class)
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<TagTeam, $this, TagTeamWrestler>
     */
    public function previousTagTeams(): BelongsToMany
    {
        return $this->tagTeams()->wherePivotNotNull('left_at');
    }

    /**
     * @return HasOneThrough<TagTeam, TagTeamWrestler, $this>
     */
    public function currentTagTeam(): HasOneThrough
    {
        return $this->hasOneThrough(
            TagTeam::class,
            TagTeamWrestler::class,
            'wrestler_id',
            'id',
            'id',
            'tag_team_id',
        )->whereNull('tag_teams_wrestlers.left_at');
    }

    /**
     * @return HasOneThrough<TagTeam, TagTeamWrestler, $this>
     */
    public function previousTagTeam(): HasOneThrough
    {
        return $this->hasOneThrough(
            TagTeam::class,
            TagTeamWrestler::class,
            'wrestler_id',
            'id',
            'id',
            'tag_team_id',
        )
            ->whereNotNull('tag_teams_wrestlers.left_at')
            ->latest('tag_teams_wrestlers.left_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'height' => HeightCast::class,
        ];
    }
}
