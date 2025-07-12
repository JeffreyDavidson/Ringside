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
 * @property EmploymentStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read TitleChampionship|null $currentChampionship
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TitleChampionship> $currentChampionships
 * @property-read WrestlerEmployment|null $currentEmployment
 * @property-read WrestlerInjury|null $currentInjury
 * @property-read TagTeamPartner|WrestlerManager|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Manager> $currentManagers
 * @property-read WrestlerRetirement|null $currentRetirement
 * @property-read Stable|null $currentStable
 * @property-read WrestlerSuspension|null $currentSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WrestlerEmployment> $employments
 * @property-read WrestlerEmployment|null $firstEmployment
 * @property-read WrestlerEmployment|null $futureEmployment
 * @property-read TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WrestlerInjury> $injuries
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Manager> $managers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventMatch> $matches
 * @property-read WrestlerEmployment|null $previousEmployment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WrestlerEmployment> $previousEmployments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WrestlerInjury> $previousInjuries
 * @property-read WrestlerInjury|null $previousInjury
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Manager> $previousManagers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventMatch> $previousMatches
 * @property-read WrestlerRetirement|null $previousRetirement
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WrestlerRetirement> $previousRetirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Stable> $previousStables
 * @property-read WrestlerSuspension|null $previousSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WrestlerSuspension> $previousSuspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TagTeam> $previousTagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TitleChampionship> $previousTitleChampionships
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WrestlerRetirement> $retirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Stable> $stables
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WrestlerSuspension> $suspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TagTeam> $tagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TitleChampionship> $titleChampionships
 * @property-read User|null $user
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
