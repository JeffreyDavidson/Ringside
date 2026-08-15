<?php

declare(strict_types=1);

namespace App\Models\Managers;

use App\Builders\Roster\ManagerBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\HasComputedEmploymentStatus;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\SoftDeletable;
use App\Models\Contracts\Suspendable;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Retirement;
use App\Models\Lifecycle\Suspension;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamManager;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerManager;
use Database\Factories\Managers\ManagerFactory;
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
 * @implements Employable<static>
 * @implements Injurable<static>
 * @implements Retirable<static>
 * @implements Suspendable<static>
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 *
 * @property-read string $full_name
 *
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
 * @property-read Collection<int, Wrestler> $wrestlers
 * @property-read Collection<int, Wrestler> $currentWrestlers
 * @property-read Collection<int, Wrestler> $previousWrestlers
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, TagTeam> $currentTagTeams
 * @property-read Collection<int, TagTeam> $previousTagTeams
 *
 * @method static ManagerBuilder<static>|Manager employed()
 * @method static \Database\Factories\Managers\ManagerFactory factory($count = null, $state = [])
 * @method static ManagerBuilder<static>|Manager futureEmployed()
 * @method static ManagerBuilder<static>|Manager newModelQuery()
 * @method static ManagerBuilder<static>|Manager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager onlyTrashed()
 * @method static ManagerBuilder<static>|Manager query()
 * @method static ManagerBuilder<static>|Manager released()
 * @method static ManagerBuilder<static>|Manager retired()
 * @method static ManagerBuilder<static>|Manager unemployed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable('first_name', 'last_name')]
#[Appends('status')]
#[UseFactory(ManagerFactory::class)]
#[UseEloquentBuilder(ManagerBuilder::class)]
class Manager extends Model implements Employable, Injurable, Retirable, SoftDeletable, Suspendable
{
    use HasComputedEmploymentStatus;

    /** @use HasFactory<ManagerFactory> */
    use HasFactory;

    /** @use IsEmployable<static> */
    use IsEmployable;

    /** @use IsInjurable<static> */
    use IsInjurable;

    /** @use IsRetirable<static> */
    use IsRetirable;

    /** @use IsSuspendable<static> */
    use IsSuspendable;

    use SoftDeletes;

    /** @return BelongsToMany<Wrestler, $this, WrestlerManager> */
    public function wrestlers(): BelongsToMany
    {
        return $this->belongsToMany(Wrestler::class, 'wrestlers_managers')
            ->using(WrestlerManager::class)
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Wrestler, $this, WrestlerManager> */
    public function currentWrestlers(): BelongsToMany
    {
        return $this->wrestlers()->wherePivotNull('fired_at');
    }

    /** @return BelongsToMany<Wrestler, $this, WrestlerManager> */
    public function previousWrestlers(): BelongsToMany
    {
        return $this->wrestlers()->wherePivotNotNull('fired_at');
    }

    /** @return BelongsToMany<TagTeam, $this, TagTeamManager> */
    public function tagTeams(): BelongsToMany
    {
        return $this->belongsToMany(TagTeam::class, 'tag_teams_managers')
            ->using(TagTeamManager::class)
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<TagTeam, $this, TagTeamManager> */
    public function currentTagTeams(): BelongsToMany
    {
        return $this->tagTeams()->wherePivotNull('fired_at');
    }

    /** @return BelongsToMany<TagTeam, $this, TagTeamManager> */
    public function previousTagTeams(): BelongsToMany
    {
        return $this->tagTeams()->wherePivotNotNull('fired_at');
    }
}
