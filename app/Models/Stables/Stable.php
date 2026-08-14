<?php

declare(strict_types=1);

namespace App\Models\Stables;

use App\Builders\Roster\StableBuilder;
use App\Enums\Stables\StableStatus;
use App\Lifecycle\StableStatusResolver;
use App\Models\Concerns\HasActivityPeriods;
use App\Models\Concerns\HasLifecycleTransitions;
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @method static StableBuilder<static>|Stable disbanded()
 * @method static StableBuilder<static>|Stable established()
 * @method static \Database\Factories\Stables\StableFactory factory($count = null, $state = [])
 * @method static StableBuilder<static>|Stable newModelQuery()
 * @method static StableBuilder<static>|Stable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stable onlyTrashed()
 * @method static StableBuilder<static>|Stable query()
 * @method static StableBuilder<static>|Stable retired()
 * @method static StableBuilder<static>|Stable unestablished()
 * @method static StableBuilder<static>|Stable withFutureEstablishment()
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

    /** @use IsRetirable<static> */
    use IsRetirable;

    use SoftDeletes;

    /** @return BelongsToMany<Wrestler, $this, StableWrestler, 'pivot'> */
    public function wrestlers(): BelongsToMany
    {
        return $this->belongsToMany(Wrestler::class, (new StableWrestler())->getTable())
            ->using(StableWrestler::class)
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Wrestler, $this, StableWrestler, 'pivot'> */
    public function currentWrestlers(): BelongsToMany
    {
        return $this->wrestlers()->wherePivotNull('left_at');
    }

    /** @return BelongsToMany<Wrestler, $this, StableWrestler, 'pivot'> */
    public function previousWrestlers(): BelongsToMany
    {
        return $this->wrestlers()->wherePivotNotNull('left_at');
    }

    /** @return BelongsToMany<TagTeam, $this, StableTagTeam, 'pivot'> */
    public function tagTeams(): BelongsToMany
    {
        return $this->belongsToMany(TagTeam::class, (new StableTagTeam())->getTable())
            ->using(StableTagTeam::class)
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<TagTeam, $this, StableTagTeam, 'pivot'> */
    public function currentTagTeams(): BelongsToMany
    {
        return $this->tagTeams()->wherePivotNull('left_at');
    }

    /** @return BelongsToMany<TagTeam, $this, StableTagTeam, 'pivot'> */
    public function previousTagTeams(): BelongsToMany
    {
        return $this->tagTeams()->wherePivotNotNull('left_at');
    }

    /** @return Attribute<StableStatus, never> */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): StableStatus => StableStatusResolver::resolve(
            isRetired: $this->isRetired(),
            isCurrentlyActive: $this->isCurrentlyActive(),
            hasFutureActivity: $this->hasFutureActivity(),
            hasActivityHistory: $this->hasActivityPeriods(),
        ));
    }
}
