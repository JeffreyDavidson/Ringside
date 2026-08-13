<?php

declare(strict_types=1);

namespace App\Models\Referees;

use App\Builders\Roster\RefereeBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Concerns\OfficiatesMatches;
use App\Models\Concerns\ProvidesDisplayName;
use App\Models\Concerns\ValidatesEmployment;
use App\Models\Concerns\ValidatesIndividualDeletion;
use App\Models\Concerns\ValidatesIndividualRestoration;
use App\Models\Concerns\ValidatesIndividualRetirement;
use App\Models\Concerns\ValidatesIndividualSuspension;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\Employable;
use App\Models\Contracts\HasDisplayName;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\SoftDeletable;
use App\Models\Contracts\Suspendable;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Retirement;
use App\Models\Lifecycle\Suspension;
use App\Models\Matches\EventMatch;
use Database\Factories\Referees\RefereeFactory;
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
#[Fillable('first_name', 'last_name')]
#[Appends('status')]
#[UseFactory(RefereeFactory::class)]
#[UseEloquentBuilder(RefereeBuilder::class)]
class Referee extends Model implements Bookable, Employable, HasDisplayName, Injurable, Retirable, SoftDeletable, Suspendable
{
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

    use OfficiatesMatches;
    use ProvidesDisplayName;
    use SoftDeletes;
    use ValidatesEmployment;
    use ValidatesIndividualDeletion;
    use ValidatesIndividualRestoration;
    use ValidatesIndividualRetirement;
    use ValidatesIndividualSuspension;

    /**
     * Get the computed status attribute.
     *
     * Computes the employment status based on the referee's current relationships:
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

    // full_name is handled by virtual column in database

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
