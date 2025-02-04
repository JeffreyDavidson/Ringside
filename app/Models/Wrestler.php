<?php

declare(strict_types=1);

namespace App\Models;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Builders\WrestlerBuilder;
use App\Casts\HeightCast;
use App\Enums\WrestlerStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Contracts\TagTeamMember;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Stable $currentStable
 * @property WrestlerEmployment $firstEmployment
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property \App\ValueObjects\Height $height
 * @property int $weight
 * @property string $hometown
 * @property string|null $signature_move
 * @property WrestlerStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \App\Models\TitleChampionship|null $currentChampionship
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TitleChampionship> $currentChampionships
 * @property-read \App\Models\WrestlerEmployment|null $currentEmployment
 * @property-read \App\Models\WrestlerInjury|null $currentInjury
 * @property-read \App\Models\TagTeamPartner|\App\Models\WrestlerManager|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Manager> $currentManagers
 * @property-read \App\Models\WrestlerRetirement|null $currentRetirement
 * @property-read \App\Models\WrestlerSuspension|null $currentSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WrestlerEmployment> $employments
 * @property-read \App\Models\WrestlerEmployment|null $futureEmployment
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WrestlerInjury> $injuries
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Manager> $managers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMatch> $matches
 * @property-read \App\Models\WrestlerEmployment|null $previousEmployment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WrestlerEmployment> $previousEmployments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WrestlerInjury> $previousInjuries
 * @property-read \App\Models\WrestlerInjury|null $previousInjury
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Manager> $previousManagers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMatch> $previousMatches
 * @property-read \App\Models\WrestlerRetirement|null $previousRetirement
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WrestlerRetirement> $previousRetirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stable> $previousStables
 * @property-read \App\Models\WrestlerSuspension|null $previousSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WrestlerSuspension> $previousSuspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam> $previousTagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TitleChampionship> $previousTitleChampionships
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WrestlerRetirement> $retirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stable> $stables
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WrestlerSuspension> $suspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam> $tagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TitleChampionship> $titleChampionships
 * @property-read \App\Models\User|null $user
 *
 * @method static WrestlerBuilder<static>|Wrestler bookable()
 * @method static WrestlerBuilder<static>|Wrestler employed()
 * @method static \Database\Factories\WrestlerFactory factory($count = null, $state = [])
 * @method static WrestlerBuilder<static>|Wrestler futureEmployed()
 * @method static WrestlerBuilder<static>|Wrestler injured()
 * @method static WrestlerBuilder<static>|Wrestler newModelQuery()
 * @method static WrestlerBuilder<static>|Wrestler newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wrestler onlyTrashed()
 * @method static WrestlerBuilder<static>|Wrestler query()
 * @method static WrestlerBuilder<static>|Wrestler released()
 * @method static WrestlerBuilder<static>|Wrestler retired()
 * @method static WrestlerBuilder<static>|Wrestler suspended()
 * @method static WrestlerBuilder<static>|Wrestler unemployed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wrestler withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wrestler withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Wrestler extends Model implements Bookable, CanBeAStableMember, Employable, Injurable, Manageable, Retirable, Suspendable, TagTeamMember
{
    use Concerns\CanJoinStables;
    use Concerns\CanJoinTagTeams;
    use Concerns\CanWinTitles;
    use Concerns\HasManagers;
    use Concerns\HasMatches;
    use Concerns\IsEmployable;
    use Concerns\IsRetirable;
    use Concerns\IsSuspendable;
    use Concerns\OwnedByUser;
    use HasBelongsToOne;

    /** @use HasBuilder<WrestlerBuilder<static>> */
    use HasBuilder;

    /** @use HasFactory<\Database\Factories\WrestlerFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'height',
        'weight',
        'hometown',
        'signature_move',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => WrestlerStatus::Unemployed->value,
    ];

    protected static string $builder = WrestlerBuilder::class;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'height' => HeightCast::class,
            'status' => WrestlerStatus::class,
        ];
    }

    /**
     * Get all the employments of the model.
     *
     * @return HasMany<WrestlerEmployment, static>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(WrestlerEmployment::class);
    }

    /**
     * @return HasMany<WrestlerRetirement, static>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(WrestlerRetirement::class);
    }

    /**
     * @return HasMany<WrestlerInjury, static>
     */
    public function injuries(): HasMany
    {
        return $this->hasMany(WrestlerInjury::class);
    }

    /**
     * @return HasMany<WrestlerSuspension, static>
     */
    public function suspensions(): HasMany
    {
        return $this->hasMany(WrestlerSuspension::class);
    }

    /**
     * Get all the managers the model has had.
     *
     * @return BelongsToMany<Manager, $this>
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class, 'wrestlers_managers')
            ->using(WrestlerManager::class)
            ->withPivot('hired_at', 'left_at');
    }

    /**
     * Get the stables the model has been belonged to.
     *
     * @return BelongsToMany<Stable, $this>
     */
    public function stables(): BelongsToMany
    {
        return $this->belongsToMany(Stable::class, 'stables_wrestlers')
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get the current stable the member belongs to.
     */
    public function currentStable(): BelongsToOne
    {
        return $this->belongsToOne(Stable::class, 'stables_wrestlers')
            ->withPivot(['joined_at', 'left_at'])
            ->wherePivotNull('left_at');
    }

    /**
     * Retrieve the event matches participated by the model.
     *
     * @return MorphToMany<EventMatch, $this>
     */
    public function matches(): MorphToMany
    {
        return $this->morphToMany(EventMatch::class, 'competitor', 'event_match_competitors');
    }
}
