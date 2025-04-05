<?php

declare(strict_types=1);

namespace App\Models;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Builders\WrestlerBuilder;
use App\Casts\HeightCast;
use App\Enums\EmploymentStatus;
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

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property \App\ValueObjects\Height $height
 * @property int $weight
 * @property string $hometown
 * @property string|null $signature_move
 * @property \App\Enums\EmploymentStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\TitleChampionship|null $currentChampionship
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TitleChampionship> $currentChampionships
 * @property-read \App\Models\WrestlerEmployment|null $currentEmployment
 * @property-read \App\Models\WrestlerInjury|null $currentInjury
 * @property-read \App\Models\TagTeamPartner|\App\Models\WrestlerManager|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Manager> $currentManagers
 * @property-read \App\Models\WrestlerRetirement|null $currentRetirement
 * @property-read \App\Models\Stable|null $currentStable
 * @property-read \App\Models\WrestlerSuspension|null $currentSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WrestlerEmployment> $employments
 * @property-read \App\Models\WrestlerEmployment|null $firstEmployment
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
 * @method static \Database\Factories\WrestlerFactory factory($count = null, $state = [])
 * @method static \App\Builders\WrestlerBuilder newModelQuery()
 * @method static \App\Builders\WrestlerBuilder newQuery()
 * @method static \App\Builders\WrestlerBuilder query()
 * @method static \App\Builders\WrestlerBuilder unemployed()
 * @method static \App\Builders\WrestlerBuilder futureEmployed()
 * @method static \App\Builders\WrestlerBuilder employed()
 * @method static \App\Builders\WrestlerBuilder bookable()
 * @method static \App\Builders\WrestlerBuilder injured()
 * @method static \App\Builders\WrestlerBuilder retired()
 * @method static \App\Builders\WrestlerBuilder released()
 * @method static \App\Builders\WrestlerBuilder suspended()
 * @method static \App\Builders\WrestlerBuilder onlyTrashed()
 * @method static \App\Builders\WrestlerBuilder withTrashed()
 * @method static \App\Builders\WrestlerBuilder withoutTrashed()
 * @method string getNameLabel()
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
    use Concerns\IsInjurable;
    use Concerns\IsRetirable;
    use Concerns\IsSuspendable;
    use Concerns\OwnedByUser;
    use HasBelongsToOne;

    /** @use HasBuilder<WrestlerBuilder> */
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
        'status' => EmploymentStatus::Unemployed->value,
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
            'status' => EmploymentStatus::class,
        ];
    }

    /**
     * Get all the employments of the model.
     *
     * @return HasMany<WrestlerEmployment, $this>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(WrestlerEmployment::class);
    }

    /**
     * @return HasMany<WrestlerRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(WrestlerRetirement::class);
    }

    /**
     * @return HasMany<WrestlerInjury, $this>
     */
    public function injuries(): HasMany
    {
        return $this->hasMany(WrestlerInjury::class);
    }

    /**
     * @return HasMany<WrestlerSuspension, $this>
     */
    public function suspensions(): HasMany
    {
        return $this->hasMany(WrestlerSuspension::class);
    }

    /**
     * Get all the managers the model has had.
     *
     * @return BelongsToMany<Manager, $this, WrestlerManager>
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

    /**
     * Retrieve the readable name of the model.
     */
    public function getNameLabel(): string
    {
        return $this->name;
    }
}
