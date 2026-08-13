<?php

declare(strict_types=1);

namespace App\Models\Stables;

use App\Builders\Concerns\HasStatusScopes;
use App\Builders\Roster\StableBuilder;
use App\Enums\Stables\StableStatus;
use App\Models\Concerns\HasActivityPeriods;
use App\Models\Concerns\HasLifecycleTransitions;
use App\Models\Concerns\HasMembers;
use App\Models\Concerns\IsRetirable;
use App\Models\Contracts\HasActivityPeriods as HasActivityPeriodsContract;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\SoftDeletable;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Lifecycle\LifecycleTransition;
use App\Models\Lifecycle\Retirement;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Stables\StableFactory;
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
use Tests\Unit\Models\Stables\StableTest;

/**
 * @implements HasActivityPeriodsContract<static>
 * @implements Retirable<static>
 *
 * @property int $id
 * @property string $name
 * @property StableStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection<int, LifecycleTransition> $lifecycleTransitions
 * @property-read Retirement|null $currentRetirement
 * @property-read Retirement|null $previousRetirement
 * @property-read Collection<int, Retirement> $retirements
 * @property-read Collection<int, Retirement> $previousRetirements
 * t
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, TagTeam> $currentTagTeams
 * @property-read Collection<int, TagTeam> $previousTagTeams
 * @property-read Collection<int, Wrestler> $wrestlers
 * @property-read Collection<int, Wrestler> $currentWrestlers
 * @property-read Collection<int, Wrestler> $previousWrestlers
 * @property-read Collection<int, ActivityPeriod> $activityPeriods
 * @property-read ActivityPeriod|null $currentActivityPeriod
 * @property-read ActivityPeriod|null $firstActivityPeriod
 * @property-read ActivityPeriod|null $futureActivityPeriod
 * @property-read ActivityPeriod|null $previousActivityPeriod
 * @property-read Collection<int, ActivityPeriod> $previousActivityPeriods
 *
 * @method static StableBuilder<static>|Stable activatedAfter(\Carbon\Carbon $date)
 * @method static StableBuilder<static>|Stable activatedBefore(\Carbon\Carbon $date)
 * @method static StableBuilder<static>|Stable active()
 * @method static StableBuilder<static>|Stable activeDuring(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static StableBuilder<static>|Stable activelyManaged()
 * @method static StableBuilder<static>|Stable available()
 * @method static StableBuilder<static>|Stable availableForReunion()
 * @method static StableBuilder<static>|Stable availableForStorylines()
 * @method static StableBuilder<static>|Stable belowMinimumMembers()
 * @method static StableBuilder<static>|Stable currentlyActive()
 * @method static StableBuilder<static>|Stable currentlyInactive()
 * @method static StableBuilder<static>|Stable deactivatedAfter(\Carbon\Carbon $date)
 * @method static StableBuilder<static>|Stable disbanded()
 * @method static StableBuilder<static>|Stable established()
 * @method static \Database\Factories\Stables\StableFactory factory($count = null, $state = [])
 * @method static StableBuilder<static>|Stable inactive()
 * @method static StableBuilder<static>|Stable neverActivated()
 * @method static StableBuilder<static>|Stable newModelQuery()
 * @method static StableBuilder<static>|Stable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stable onlyTrashed()
 * @method static StableBuilder<static>|Stable query()
 * @method static StableBuilder<static>|Stable retired()
 * @method static StableBuilder<static>|Stable unactivated()
 * @method static StableBuilder<static>|Stable unavailable()
 * @method static StableBuilder<static>|Stable unestablished()
 * @method static StableBuilder<static>|Stable withAvailableMembers()
 * @method static StableBuilder<static>|Stable withFutureActivation()
 * @method static StableBuilder<static>|Stable withFutureEstablishment()
 * @method static StableBuilder<static>|Stable withMemberCount(int $min, ?int $max = null)
 * @method static StableBuilder<static>|Stable withMinimumMembers()
 * @method static StableBuilder<static>|Stable withMultiplePeriods(int $minimumPeriods = 2)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stable withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stable withoutTrashed()
 *
 * @mixin \Eloquent
 *
 * @see StableTest
 */
#[Fillable('name')]
#[Appends('status')]
#[UseFactory(StableFactory::class)]
#[UseEloquentBuilder(StableBuilder::class)]
class Stable extends Model implements HasActivityPeriodsContract, Retirable, SoftDeletable
{
    /** @use HasActivityPeriods<static> */
    use HasActivityPeriods;

    /** @use HasFactory<StableFactory> */
    use HasFactory;

    use HasLifecycleTransitions;
    use HasMembers;
    use HasStatusScopes;

    /** @use IsRetirable<static> */
    use IsRetirable;

    use SoftDeletes;

    /**
     * The minimum number of members allowed on a tag team.
     */
    public const int MIN_MEMBERS_COUNT = 3;

    /**
     * Get the computed status attribute.
     *
     * Computes the stable status based on activity periods and retirement state:
     * - Retired: Has active retirement record
     * - Active: Has current activity period
     * - PendingEstablishment: Has future activity period
     * - Inactive: Has previous activity periods but no current activity
     * - Unformed: No activity periods at all
     *
     * @return Attribute<StableStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (): StableStatus {
                // Check for retirement first
                if ($this->isRetired()) {
                    return StableStatus::Retired;
                }

                // Check for current activity
                if ($this->isCurrentlyActive()) {
                    return StableStatus::Active;
                }

                // Check for future activity
                if ($this->hasFutureActivity()) {
                    return StableStatus::PendingEstablishment;
                }

                // Check for previous activity periods
                if ($this->hasActivityPeriods()) {
                    return StableStatus::Inactive;
                }

                // No activity periods at all
                return StableStatus::Unformed;
            }
        );
    }

    /**
     * Check if the stable is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === StableStatus::Active;
    }

    /**
     * Check if the stable is disbanded (inactive status).
     */
    public function isDisbanded(): bool
    {
        return $this->status === StableStatus::Inactive;
    }

    /**
     * Determine if the stable has a future establishment scheduled.
     */
    public function hasFutureEstablishment(): bool
    {
        return $this->hasFutureActivity();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StableStatus::class,
        ];
    }
}
