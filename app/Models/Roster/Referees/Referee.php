<?php

declare(strict_types=1);

namespace App\Models\Roster\Referees;

use App\Builders\Matches\EventMatchBuilder;
use App\Builders\Roster\RefereeBuilder;
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
use App\Models\Matches\EventMatch;
use Database\Factories\Roster\Referees\RefereeFactory;
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
 * @property-read Collection<int, EventMatch> $matches
 * @property-read Collection<int, EventMatch> $previousMatches
 *
 * @method static RefereeBuilder<static>|Referee employed()
 * @method static \Database\Factories\Roster\Referees\RefereeFactory factory($count = null, $state = [])
 * @method static RefereeBuilder<static>|Referee futureEmployed()
 * @method static RefereeBuilder<static>|Referee newModelQuery()
 * @method static RefereeBuilder<static>|Referee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Referee onlyTrashed()
 * @method static RefereeBuilder<static>|Referee query()
 * @method static RefereeBuilder<static>|Referee released()
 * @method static RefereeBuilder<static>|Referee retired()
 * @method static RefereeBuilder<static>|Referee unemployed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Referee withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Referee withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable('first_name', 'last_name')]
#[Appends('status')]
#[UseFactory(RefereeFactory::class)]
#[UseEloquentBuilder(RefereeBuilder::class)]
class Referee extends Model implements Employable, Injurable, Retirable, SoftDeletable, Suspendable
{
    use HasComputedEmploymentStatus;

    /** @use HasFactory<RefereeFactory> */
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

    /** @return BelongsToMany<EventMatch, $this> */
    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(
            EventMatch::class,
            'events_matches_referees',
            'referee_id',
            'match_id',
        );
    }

    /** @return BelongsToMany<EventMatch, $this> */
    public function previousMatches(): BelongsToMany
    {
        $relation = $this->matches();
        EventMatchBuilder::constrainToPastEvents($relation->getQuery());

        return $relation;
    }
}
