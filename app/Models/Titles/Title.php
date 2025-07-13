<?php

declare(strict_types=1);

namespace App\Models\Titles;

use App\Builders\Titles\TitleBuilder;
use App\Database\Query\Concerns\HasStatusScopes;
use App\Enums\Titles\TitleStatus;
use App\Enums\Titles\TitleType;
use App\Models\Concerns\HasActivityPeriods;
use App\Models\Concerns\HasChampionships;
use App\Models\Concerns\HasStatusHistory;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\ProvidesDisplayName;
use App\Models\Concerns\ValidatesActivation;
use App\Models\Concerns\ValidatesRetirement;
use App\Models\Contracts\Debutable;
use App\Models\Contracts\HasActivityPeriods as HasActivityPeriodsContract;
use App\Models\Contracts\HasDisplayName;
use App\Models\Contracts\Retirable;
use Database\Factories\Titles\TitleFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @implements Debutable<TitleStatusChange, static>
 * @implements Retirable<TitleRetirement, static>
 *
 * @property int $id
 * @property string $name
 * @property TitleStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read TitleStatusChange|null $debutStatusChange
 * @property-read TitleStatusChange|null $latestStatusChange
 * @property-read Collection<int, TitleStatusChange> $statusChanges
 * @property-read TitleRetirement|null $currentRetirement
 * @property-read TitleRetirement|null $previousRetirement
 * @property-read Collection<int, TitleRetirement> $retirements
 * @property-read Collection<int, TitleRetirement> $previousRetirements
 * @property-read TitleChampionship|null $currentChampionship
 * @property-read Collection<int, TitleChampionship> $championships
 * @property TitleType $type
 * @property string|null $current_champion_type
 * @property int|null $current_champion_id
 * @property string|null $previous_champion_type
 * @property int|null $previous_champion_id
 * @property-read Collection<int, TitleActivityPeriod> $activations
 * @property-read Collection<int, TitleActivityPeriod> $activityPeriods
 * @property-read TitleActivityPeriod|null $currentActivityPeriod
 * @property-read mixed $display_name
 * @property-read TitleActivityPeriod|null $firstActivityPeriod
 * @property-read TitleActivityPeriod|null $futureActivityPeriod
 * @property-read TitleActivityPeriod|null $previousActivityPeriod
 * @property-read Collection<int, TitleActivityPeriod> $previousActivityPeriods
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
#[UseFactory(TitleFactory::class)]
#[UseEloquentBuilder(TitleBuilder::class)]
class Title extends Model implements Debutable, HasActivityPeriodsContract, HasDisplayName, Retirable
{
    /** @use HasActivityPeriods<TitleActivityPeriod, static> */
    use HasActivityPeriods {
        HasActivityPeriods::isCurrentlyActive insteadof HasStatusHistory;
        HasActivityPeriods::isNotCurrentlyActive insteadof HasStatusHistory;
        HasActivityPeriods::isUnactivated insteadof HasStatusHistory;
        HasActivityPeriods::isInactive insteadof HasStatusHistory;
    }

    use HasChampionships;
    use HasFactory;

    /** @use HasStatusHistory<TitleStatusChange, static> */
    use HasStatusHistory;

    use HasStatusScopes;

    /** @use IsRetirable<TitleRetirement, static> */
    use IsRetirable;

    use ProvidesDisplayName;
    use SoftDeletes;
    use ValidatesActivation;
    use ValidatesRetirement;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
        'type',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => TitleStatus::Undebuted->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TitleStatus::class,
            'type' => TitleType::class,
        ];
    }

    /**
     * Determine if the title has a future debut scheduled.
     */
    public function hasFutureDebut(): bool
    {
        return $this->hasFutureActivity();
    }

    /**
     * Check if this is a singles title.
     */
    public function isSinglesTitle(): bool
    {
        return $this->type === TitleType::Singles;
    }

    /**
     * Check if this is a tag team title.
     */
    public function isTagTeamTitle(): bool
    {
        return $this->type === TitleType::TagTeam;
    }
}
