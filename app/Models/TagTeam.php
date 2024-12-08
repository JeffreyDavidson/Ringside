<?php

declare(strict_types=1);

namespace App\Models;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Builders\TagTeamBuilder;
use App\Enums\TagTeamStatus;
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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property \App\Enums\TagTeamStatus $status
 */
class TagTeam extends Model implements Bookable, CanBeAStableMember, Employable, Manageable, Retirable, Suspendable
{
    use Concerns\CanWinTitles;
    use Concerns\HasMatches;
    use Concerns\HasWrestlers;
    use Concerns\OwnedByUser;
    use HasBelongsToOne;

    /** @use HasBuilder<TagTeamBuilder<static>> */
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
        'status' => TagTeamStatus::Unemployed->value,
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
            'status' => TagTeamStatus::class,
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
     * @return HasOne<TagTeamEmployment, $this>
     */
    public function currentEmployment(): HasOne
    {
        return $this->employments()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasOne<TagTeamEmployment, $this>
     */
    public function futureEmployment(): HasOne
    {
        return $this->employments()
            ->whereNull('ended_at')
            ->where('started_at', '>', now())
            ->one();
    }

    /**
     * @return HasMany<TagTeamEmployment, $this>
     */
    public function previousEmployments(): HasMany
    {
        return $this->employments()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<TagTeamEmployment, $this>
     */
    public function previousEmployment(): HasOne
    {
        return $this->previousEmployments()
            ->one()
            ->ofMany('ended_at', 'max');
    }

    /**
     * @return HasOne<TagTeamEmployment, $this>
     */
    public function firstEmployment(): HasOne
    {
        return $this->employments()
            ->one()
            ->ofMany('started_at', 'min');
    }

    public function hasEmployments(): bool
    {
        return $this->employments()->count() > 0;
    }

    public function isCurrentlyEmployed(): bool
    {
        return $this->currentEmployment()->exists();
    }

    public function hasFutureEmployment(): bool
    {
        return $this->futureEmployment()->exists();
    }

    public function isNotInEmployment(): bool
    {
        return $this->isUnemployed() || $this->isReleased() || $this->isRetired();
    }

    public function isUnemployed(): bool
    {
        return $this->employments()->count() === 0;
    }

    public function isReleased(): bool
    {
        return $this->previousEmployment()->exists()
            && $this->futureEmployment()->doesntExist()
            && $this->currentEmployment()->doesntExist()
            && $this->currentRetirement()->doesntExist();
    }

    public function employedOn(Carbon $employmentDate): bool
    {
        return $this->currentEmployment ? $this->currentEmployment->started_at->eq($employmentDate) : false;
    }

    public function employedBefore(Carbon $employmentDate): bool
    {
        return $this->currentEmployment ? $this->currentEmployment->started_at->lte($employmentDate) : false;
    }

    /**
     * @return HasMany<TagTeamRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(TagTeamRetirement::class);
    }

    /**
     * @return HasOne<TagTeamRetirement, $this>
     */
    public function currentRetirement(): HasOne
    {
        return $this->retirements()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<TagTeamRetirement, $this>
     */
    public function previousRetirements(): HasMany
    {
        return $this->retirements()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<TagTeamRetirement, $this>
     */
    public function previousRetirement(): HasOne
    {
        return $this->previousRetirements()
            ->one()
            ->ofMany('ended_at', 'max');
    }

    public function isRetired(): bool
    {
        return $this->currentRetirement()->exists();
    }

    public function hasRetirements(): bool
    {
        return $this->retirements()->count() > 0;
    }

    /**
     * @return HasMany<TagTeamSuspension, $this>
     */
    public function suspensions(): HasMany
    {
        return $this->hasMany(TagTeamSuspension::class);
    }

    /**
     * @return HasOne<TagTeamSuspension, $this>
     */
    public function currentSuspension(): HasOne
    {
        return $this->suspensions()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<TagTeamSuspension, $this>
     */
    public function previousSuspensions(): HasMany
    {
        return $this->suspensions()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<TagTeamSuspension, $this>
     */
    public function previousSuspension(): HasOne
    {
        return $this->suspensions()
            ->one()
            ->ofMany('ended_at', 'max');
    }

    public function isSuspended(): bool
    {
        return $this->currentSuspension()->exists();
    }

    public function hasSuspensions(): bool
    {
        return $this->suspensions()->count() > 0;
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
     * Get all the current managers the model has.
     *
     * @return BelongsToMany<Manager, $this>
     */
    public function currentManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all the previous managers the model has had.
     *
     * @return BelongsToMany<Manager, $this>
     */
    public function previousManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivotNotNull('left_at');
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
     * Get the previous stables the member has belonged to.
     *
     * @return BelongsToMany<Stable, $this>
     */
    public function previousStables(): BelongsToMany
    {
        return $this->stables()
            ->wherePivot('joined_at', '<', now())
            ->wherePivotNotNull('left_at');
    }

    /**
     * Check to see if the tag team is bookable.
     */
    public function isBookable(): bool
    {
        return $this->status->value === TagTeamStatus::Bookable->value;
    }

    /**
     * Check to see if the tag team is unbookable.
     */
    public function isUnbookable(): bool
    {
        return ! $this->currentWrestlers->every(fn (Wrestler $wrestler) => $wrestler->isBookable());
    }
}
