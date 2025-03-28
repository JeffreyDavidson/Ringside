<?php

declare(strict_types=1);

namespace App\Models;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Builders\TagTeamBuilder;
use App\Enums\EmploymentStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
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
 * @property string|null $signature_move
 * @property \App\Enums\EmploymentStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read mixed $combined_weight
 * @property-read \App\Models\TitleChampionship|null $currentChampionship
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TitleChampionship> $currentChampionships
 * @property-read \App\Models\TagTeamEmployment|null $currentEmployment
 * @property-read \App\Models\TagTeamPartner|\App\Models\TagTeamManager|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Manager> $currentManagers
 * @property-read \App\Models\TagTeamRetirement|null $currentRetirement
 * @property-read \App\Models\Stable|null $currentStable
 * @property-read \App\Models\TagTeamSuspension|null $currentSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler> $currentWrestlers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeamEmployment> $employments
 * @property-read \App\Models\TagTeamEmployment|null $firstEmployment
 * @property-read \App\Models\TagTeamEmployment|null $futureEmployment
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Manager> $managers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMatch> $matches
 * @property-read \App\Models\TagTeamEmployment|null $previousEmployment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeamEmployment> $previousEmployments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Manager> $previousManagers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMatch> $previousMatches
 * @property-read \App\Models\TagTeamRetirement|null $previousRetirement
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeamRetirement> $previousRetirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stable> $previousStables
 * @property-read \App\Models\TagTeamSuspension|null $previousSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeamSuspension> $previousSuspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TitleChampionship> $previousTitleChampionships
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler> $previousWrestlers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeamRetirement> $retirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stable> $stables
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeamSuspension> $suspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TitleChampionship> $titleChampionships
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler> $wrestlers
 *
 * @method static \Database\Factories\TagTeamFactory factory($count = null, $state = [])
 * @method static \App\Builders\TagTeamBuilder newModelQuery()
 * @method static \App\Builders\TagTeamBuilder newQuery()
 * @method static \App\Builders\TagTeamBuilder query()
 * @method static \App\Builders\TagTeamBuilder bookable()
 * @method static \App\Builders\TagTeamBuilder unbookable()
 * @method static \App\Builders\TagTeamBuilder retired()
 * @method static \App\Builders\TagTeamBuilder unemployed()
 * @method static \App\Builders\TagTeamBuilder suspended()
 * @method static \App\Builders\TagTeamBuilder released()
 * @method static \App\Builders\TagTeamBuilder futureEmployed()
 * @method static \App\Builders\TagTeamBuilder onlyTrashed()
 * @method static \App\Builders\TagTeamBuilder withTrashed()
 * @method static \App\Builders\TagTeamBuilder withoutTrashed()
 *
 * @mixin \Eloquent
 */
class TagTeam extends Model implements Bookable, CanBeAStableMember, Employable, Manageable, Retirable, Suspendable
{
    use Concerns\CanJoinStables;
    use Concerns\CanWinTitles;
    use Concerns\HasManagers;
    use Concerns\HasMatches;
    use Concerns\HasWrestlers;
    use Concerns\IsEmployable;
    use Concerns\IsRetirable;
    use Concerns\IsSuspendable;
    use Concerns\OwnedByUser;
    use HasBelongsToOne;

    /** @use HasBuilder<TagTeamBuilder> */
    use HasBuilder;

    /** @use HasFactory<\Database\Factories\TagTeamFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The number of the wrestlers allowed on a tag team.
     */
    public const int NUMBER_OF_WRESTLERS_ON_TEAM = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
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

    protected static string $builder = TagTeamBuilder::class;

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
     * Get all the employments of the model.
     *
     * @return HasMany<TagTeamEmployment, $this>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(TagTeamEmployment::class);
    }

    /**
     * @return HasMany<TagTeamRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(TagTeamRetirement::class);
    }

    /**
     * @return HasMany<TagTeamSuspension, $this>
     */
    public function suspensions(): HasMany
    {
        return $this->hasMany(TagTeamSuspension::class);
    }

    /**
     * Get all the managers the model has had.
     *
     * @return BelongsToMany<Manager, $this>
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class, 'tag_teams_managers')
            ->using(TagTeamManager::class)
            ->withPivot('hired_at', 'left_at');
    }

    /**
     * Get the stables the model has been belonged to.
     *
     * @return BelongsToMany<Stable, $this>
     */
    public function stables(): BelongsToMany
    {
        return $this->belongsToMany(Stable::class, 'stables_tag_teams')
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get the current stable the member belongs to.
     */
    public function currentStable(): BelongsToOne
    {
        return $this->belongsToOne(Stable::class, 'stables_tag_teams')
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
     * Check to see if the tag team is bookable.
     */
    public function isBookable(): bool
    {
        return $this->status->value === EmploymentStatus::Bookable->value;
    }

    /**
     * Check to see if the tag team is unbookable.
     */
    public function isUnbookable(): bool
    {
        return ! $this->currentWrestlers->every(fn (Wrestler $wrestler) => $wrestler->isBookable());
    }

    /**
     * Retrieve the name label of the model.
     */
    public function getNameLabel(): string
    {
        return $this->name;
    }
}
