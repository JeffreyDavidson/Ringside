<?php

declare(strict_types=1);

namespace App\Models\Titles;

use App\Builders\Concerns\HasStatusScopes;
use App\Builders\Titles\TitleBuilder;
use App\Enums\Titles\TitleStatus;
use App\Enums\Titles\TitleType;
use App\Models\Concerns\HasActivityPeriods;
use App\Models\Concerns\HasLifecycleTransitions;
use App\Models\Concerns\IsRetirable;
use App\Models\Contracts\HasActivityPeriods as HasActivityPeriodsContract;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\SoftDeletable;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Lifecycle\LifecycleTransition;
use App\Models\Lifecycle\Retirement;
use Database\Factories\Titles\TitleFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @implements HasActivityPeriodsContract<static>
 * @implements Retirable<static>
 *
 * @property int $id
 * @property string $name
 * @property TitleStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection<int, LifecycleTransition> $lifecycleTransitions
 * @property-read Retirement|null $currentRetirement
 * @property-read Retirement|null $previousRetirement
 * @property-read Collection<int, Retirement> $retirements
 * @property-read Collection<int, Retirement> $previousRetirements
 * @property-read TitleChampionship|null $currentChampionship
 * @property-read Collection<int, TitleChampionship> $championships
 *
 * @property TitleType $type
 * @property string|null $current_champion_type
 * @property int|null $current_champion_id
 * @property string|null $previous_champion_type
 * @property int|null $previous_champion_id
 *
 * @property-read Collection<int, ActivityPeriod> $activityPeriods
 * @property-read ActivityPeriod|null $currentActivityPeriod
 * @property-read ActivityPeriod|null $firstActivityPeriod
 * @property-read ActivityPeriod|null $futureActivityPeriod
 * @property-read ActivityPeriod|null $previousActivityPeriod
 * @property-read Collection<int, ActivityPeriod> $previousActivityPeriods
 *
 * @method static TitleBuilder<static>|Title activatedAfter(\Carbon\Carbon $date)
 * @method static TitleBuilder<static>|Title activatedBefore(\Carbon\Carbon $date)
 * @method static TitleBuilder<static>|Title active()
 * @method static TitleBuilder<static>|Title activeDuring(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static TitleBuilder<static>|Title available()
 * @method static TitleBuilder<static>|Title competable()
 * @method static TitleBuilder<static>|Title currentlyActive()
 * @method static TitleBuilder<static>|Title currentlyInactive()
 * @method static TitleBuilder<static>|Title deactivatedAfter(\Carbon\Carbon $date)
 * @method static TitleBuilder<static>|Title defended()
 * @method static \Database\Factories\Titles\TitleFactory factory($count = null, $state = [])
 * @method static TitleBuilder<static>|Title inactive()
 * @method static TitleBuilder<static>|Title neverActivated()
 * @method static TitleBuilder<static>|Title newModelQuery()
 * @method static TitleBuilder<static>|Title newQuery()
 * @method static TitleBuilder<static>|Title newTitles()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Title onlyTrashed()
 * @method static TitleBuilder<static>|Title query()
 * @method static TitleBuilder<static>|Title retired()
 * @method static TitleBuilder<static>|Title unavailable()
 * @method static TitleBuilder<static>|Title undebuted()
 * @method static TitleBuilder<static>|Title unretired()
 * @method static TitleBuilder<static>|Title vacant()
 * @method static TitleBuilder<static>|Title withMultiplePeriods(int $minimumPeriods = 2)
 * @method static TitleBuilder<static>|Title withPendingDebut()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Title withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Title withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable('name', 'type')]
#[Appends('status')]
#[UseFactory(TitleFactory::class)]
#[UseEloquentBuilder(TitleBuilder::class)]
class Title extends Model implements HasActivityPeriodsContract, Retirable, SoftDeletable
{
    /** @use HasActivityPeriods<static> */
    use HasActivityPeriods;

    /** @use HasFactory<TitleFactory> */
    use HasFactory;

    use HasLifecycleTransitions;
    use HasStatusScopes;

    /** @use IsRetirable<static> */
    use IsRetirable;

    use SoftDeletes;

    /** @return HasMany<TitleChampionship, $this> */
    public function championships(): HasMany
    {
        return $this->hasMany(TitleChampionship::class)->oldest('won_at');
    }

    /** @return HasOne<TitleChampionship, $this> */
    public function currentChampionship(): HasOne
    {
        return $this->hasOne(TitleChampionship::class)
            ->whereNull('lost_at')
            ->latest('won_at');
    }

    /**
     * Get the computed status attribute.
     *
     * Computes the title status based on activity periods and retirement state:
     * - Active: Has current activity period
     * - PendingDebut: Has future activity period
     * - Inactive: Has previous activity periods but no current activity
     * - Undebuted: No activity periods at all
     *
     * @return Attribute<TitleStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (): TitleStatus {
                // Check for current activity first
                if ($this->isCurrentlyActive()) {
                    return TitleStatus::Active;
                }

                // Check for future activity
                if ($this->hasFutureActivity()) {
                    return TitleStatus::PendingDebut;
                }

                // Check for previous activity periods
                if ($this->hasActivityPeriods()) {
                    return TitleStatus::Inactive;
                }

                // No activity periods at all
                return TitleStatus::Undebuted;
            }
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TitleType::class,
        ];
    }
}
