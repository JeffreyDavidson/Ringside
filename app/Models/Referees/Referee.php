<?php

declare(strict_types=1);

namespace App\Models\Referees;

use App\Builders\Roster\RefereeBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\HasMatches;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Concerns\ProvidesDisplayName;
use App\Models\Concerns\ValidatesEmployment;
use App\Models\Concerns\ValidatesInjury;
use App\Models\Concerns\ValidatesRetirement;
use App\Models\Concerns\ValidatesSuspension;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\Employable;
use App\Models\Contracts\HasDisplayName;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Matches\EventMatch;
use Database\Factories\Referees\RefereeFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @implements Employable<RefereeEmployment, static>
 * @implements Injurable<RefereeInjury, static>
 * @implements Retirable<RefereeRetirement, static>
 * @implements Suspendable<RefereeSuspension, static>
 * @implements Bookable<Referee, static>
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property-read string $full_name
 * @property EmploymentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read RefereeEmployment|null $currentEmployment
 * @property-read RefereeEmployment|null $firstEmployment
 * @property-read RefereeEmployment|null $futureEmployment
 * @property-read RefereeEmployment|null $previousEmployment
 * @property-read Collection<int, RefereeEmployment> $employments
 * @property-read Collection<int, RefereeEmployment> $previousEmployments
 * @property-read RefereeInjury|null $currentInjury
 * @property-read RefereeInjury|null $previousInjury
 * @property-read Collection<int, RefereeInjury> $injuries
 * @property-read Collection<int, RefereeInjury> $previousInjuries
 * @property-read RefereeRetirement|null $currentRetirement
 * @property-read RefereeRetirement|null $previousRetirement
 * @property-read Collection<int, RefereeRetirement> $retirements
 * @property-read Collection<int, RefereeRetirement> $previousRetirements
 * @property-read RefereeSuspension|null $currentSuspension
 * @property-read RefereeSuspension|null $previousSuspension
 * @property-read Collection<int, RefereeSuspension> $suspensions
 * @property-read Collection<int, RefereeSuspension> $previousSuspensions
 * @property-read Collection<int, EventMatch> $matches
 * @property-read Collection<int, EventMatch> $previousMatches
 * @property-read mixed $display_name
 *
 * @method static RefereeBuilder<static>|Referee available()
 * @method static RefereeBuilder<static>|Referee availableOn(\Carbon\Carbon $date)
 * @method static RefereeBuilder<static>|Referee bookable()
 * @method static RefereeBuilder<static>|Referee employed()
 * @method static \Database\Factories\Referees\RefereeFactory factory($count = null, $state = [])
 * @method static RefereeBuilder<static>|Referee futureEmployed()
 * @method static RefereeBuilder<static>|Referee injured()
 * @method static RefereeBuilder<static>|Referee newModelQuery()
 * @method static RefereeBuilder<static>|Referee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Referee onlyTrashed()
 * @method static RefereeBuilder<static>|Referee query()
 * @method static RefereeBuilder<static>|Referee released()
 * @method static RefereeBuilder<static>|Referee retired()
 * @method static RefereeBuilder<static>|Referee suspended()
 * @method static RefereeBuilder<static>|Referee unavailable()
 * @method static RefereeBuilder<static>|Referee unemployed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Referee withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Referee withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[UseFactory(RefereeFactory::class)]
#[UseEloquentBuilder(RefereeBuilder::class)]
class Referee extends Model implements Bookable, Employable, HasDisplayName, Injurable, Retirable, Suspendable
{
    use HasFactory;
    use HasMatches;

    /** @use IsEmployable<RefereeEmployment, static> */
    use IsEmployable;

    /** @use IsInjurable<RefereeInjury, static> */
    use IsInjurable;

    /** @use IsRetirable<RefereeRetirement, static> */
    use IsRetirable;

    /** @use IsSuspendable<RefereeSuspension, static> */
    use IsSuspendable;

    use ProvidesDisplayName;
    use SoftDeletes;
    use ValidatesEmployment;
    use ValidatesInjury;
    use ValidatesRetirement;
    use ValidatesSuspension {
        ValidatesSuspension::isUnemployed insteadof ValidatesInjury;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
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
        return ! ($this->isNotInEmployment() || $this->isSuspended() || $this->isInjured() || $this->hasFutureEmployment());
    }
}
