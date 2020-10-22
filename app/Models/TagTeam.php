<?php

namespace App\Models;

use App\Enums\TagTeamStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeReleasedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Concerns\CanBeStableMember;
use Carbon\Carbon;
use Exception;
use Fidum\EloquentMorphToOne\HasMorphToOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends Model
{
    use SoftDeletes,
        HasFactory,
        HasMorphToOne,
        CanBeStableMember,
        Concerns\Unguarded;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($tagTeam) {
            $tagTeam->updateStatus();
        });
    }

    /**
     * The number of the wrestlers allowed on a tag team.
     *
     * @var int
     */
    const MAX_WRESTLERS_COUNT = 2;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['currentWrestlers'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => TagTeamStatus::class,
    ];

    /**
     * Get the user belonging to the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wrestlers that have been tag team partners of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wrestlers()
    {
        return $this->belongsToMany(Wrestler::class, 'tag_team_wrestler')
                    ->withPivot('joined_at', 'left_at');
    }

    /**
     * Get current tag team partners of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function currentWrestlers()
    {
        return $this->wrestlers()
                    ->wherePivot('joined_at', '<=', now())
                    ->wherePivot('left_at', '=', null)
                    ->limit(2);
    }

    /**
     * Get previous tag team partners of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousWrestlers()
    {
        return $this->wrestlers()
                    ->whereNotNull('left_at');
    }

    /**
     * Get the combined weight of both tag team partners in a tag team.
     *
     * @return int
     */
    public function getCombinedWeightAttribute()
    {
        return $this->currentWrestlers->sum('weight');
    }

    /**
     * Add wrestlers to a tag team.
     *
     * @param  array  $wrestlers
     * @param  string|null $dateJoined
     *
     * @throws Exception
     *
     * @return $this
     */
    public function addWrestlers($wrestlerIds, $dateJoined = null)
    {
        if (count($wrestlerIds) !== self::MAX_WRESTLERS_COUNT) {
            throw new Exception('The required number of wrestlers to join a tag team must be two.');
        }

        $dateJoined ?? now();

        $this->wrestlers()->sync([
            $wrestlerIds[0] => ['joined_at' => $dateJoined],
            $wrestlerIds[1] => ['joined_at' => $dateJoined],
        ]);

        return $this;
    }

    /**
     * Get all of the employments of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function employments()
    {
        return $this->morphMany(Employment::class, 'employable');
    }

    /**
     * Get the current employment of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentEmployment()
    {
        return $this->morphOne(Employment::class, 'employable')
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the future employment of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function futureEmployment()
    {
        return $this->morphOne(Employment::class, 'employable')
                    ->where('started_at', '>', now())
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the previous employments of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousEmployments()
    {
        return $this->employments()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous employment of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousEmployment()
    {
        return $this->morphOne(Employment::class, 'employable')
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include future employmed tag teams.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFutureEmployment($query)
    {
        return $query->whereHas('futureEmployment');
    }

    /**
     * Scope a query to only include employed tag teams.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmployed($query)
    {
        return $query->whereHas('currentEmployment')
                    ->whereDoesntHave('currentSuspension');
    }

    /**
     * Scope a query to only include released tag teams.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReleased($query)
    {
        return $query->whereHas('previousEmployment')
                    ->whereDoesntHave('currentEmployment')
                    ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to only include unemployed tag teams.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnemployed($query)
    {
        return $query->whereDoesntHave('currentEmployment');
    }

    /**
     * Scope a query to include first employment date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFirstEmployedAtDate($query)
    {
        return $query->addSelect(['first_employed_at' => Employment::select('started_at')
            ->whereColumn('employable_id', $query->qualifyColumn('id'))
            ->where('employable_type', $this->getMorphClass())
            ->orderBy('started_at', 'desc')
            ->limit(1),
        ])->withCasts(['first_employed_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the tag team's first employment date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByFirstEmployedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(first_employed_at) $direction");
    }

    /**
     * Scope a query to include released date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithReleasedAtDate($query)
    {
        return $query->addSelect(['released_at' => Employment::select('ended_at')
            ->whereColumn('employable_id', $this->getTable().'.id')
            ->where('employable_type', $this->getMorphClass())
            ->orderBy('ended_at', 'desc')
            ->limit(1),
        ])->withCasts(['released_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the tag team's current released date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentReleasedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_released_at) $direction");
    }

    /**
     * Employ a tag team.
     *
     * @param  string|null $startAtDate
     * @return $this
     */
    public function employ($startAtDate = null)
    {
        throw_unless($this->canBeEmployed(), new CannotBeEmployedException('Tag Team cannot be employed. Tag Team is already employed.'));

        $startAtDate = Carbon::parse($startAtDate)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $this->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startAtDate]);

        if ($this->currentWrestlers->every->isNotInEmployment()) {
            $this->currentWrestlers->each->employ($startAtDate);
        }

        $this->updateStatusAndSave();
    }

    /**
     * Release a tag team.
     *
     * @param  string|null $releasedAt
     * @return $this
     */
    public function release($releasedAt = null)
    {
        throw_unless($this->canBeReleased(), new CannotBeReleasedException('Tag Team cannot be released. Tag Team does not have an active employment.'));
    }

    /**
     * Check to see if the tag team is employed.
     *
     * @return bool
     */
    public function isCurrentlyEmployed()
    {
        return $this->currentEmployment()->exists();
    }

    public function isNotInEmployment()
    {
        return $this->isUnemployed() || $this->isReleased() || $this->hasFutureEmployment() || $this->isRetired();
    }

    /**
     * Check to see if the tag team is unemployed.
     *
     * @return bool
     */
    public function isUnemployed()
    {
        return $this->employments()->count() === 0;
    }

    /**
     * Check to see if the tag team has a future employment set.
     *
     * @return bool
     */
    public function hasFutureEmployment()
    {
        return $this->futureEmployment()->exists();
    }

    /**
     * Check to see if the tag team has been released.
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->previousEmployment()->exists() &&
                $this->currentEmployment()->doesntExist() &&
                $this->currentRetirement()->doesntExist();
    }

    /**
     * Determine if the tag team can be employed.
     *
     * @return bool
     */
    public function canBeEmployed()
    {
        if ($this->isCurrentlyEmployed()) {
            // throw new CannotBeEmployedException('Tag Team cannot be employed. This Tag Team does not have an active employment.');
            return false;
        }

        if ($this->hasFutureEmployment() && $this->currentWrestlers->count() !== self::MAX_WRESTLERS_COUNT) {
            // throw new CannotBeEmployedException('Tag Team cannot be employed. This Tag Team does not have 2 tag team partners.');
            return false;
        }

        return true;
    }

    /**
     * Determine if the tag team can be released.
     *
     * @return bool
     */
    public function canBeReleased()
    {
        if ($this->isNotInEmployment()) {
            // throw new CannotBeEmployedException('Entity cannot be released. This entity does not have an active employment.');
            return false;
        }

        return true;
    }

    /**
     * Get the tag team's first employment date.
     *
     * @return string|null
     */
    public function getStartedAtAttribute()
    {
        return optional($this->employments->last())->started_at;
    }

    /**
     * Get the retirements of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements()
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentRetirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the previous retirements of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousRetirements()
    {
        return $this->retirements()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous retirement of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousRetirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include retired tag teams.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRetired($query)
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Scope a query to include current retirement date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrentRetiredAtDate($query)
    {
        return $query->addSelect(['current_retired_at' => Retirement::select('started_at')
            ->whereColumn('retiree_id', $this->getTable().'.id')
            ->where('retiree_type', $this->getMorphClass())
            ->oldest('started_at')
            ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the tag team's current retirement date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string|null $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentRetiredAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_retired_at) $direction");
    }

    /**
     * Retire a tag team.
     *
     * @param  string|null $retiredAt
     * @return $this
     */
    public function retire($retiredAt = null)
    {
        throw_unless($this->canBeRetired(), new CannotBeRetiredException('Tag Team cannot be retired. This Tag Team does not have an active employment.'));

        $retiredDate = $retiredAt ?: now();

        if ($this->isSuspended()) {
            $this->reinstate($retiredAt);
        }

        $this->currentEmployment()->update(['ended_at' => $retiredDate]);
        $this->retirements()->create(['started_at' => $retiredDate]);
        $this->currentWrestlers->each->retire($retiredDate);
        $this->updateStatusAndSave();
    }

    /**
     * Unretire a tag team.
     *
     * @param  string|null $unretiredAt
     * @return void
     */
    public function unretire($unretiredAt = null)
    {
        throw_unless($this->canBeUnretired(), new CannotBeUnretiredException('Tag Team cannot be unretired. This Tag Team is not retired.'));

        $unretiredDate = $unretiredAt ?: now();

        $this->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $this->currentWrestlers->each->unretire($unretiredDate);
        $this->updateStatusAndSave();

        $this->employ($unretiredDate);
        $this->updateStatusAndSave();
    }

    /**
     * Check to see if the tag team is retired.
     *
     * @return bool
     */
    public function isRetired()
    {
        return $this->currentRetirement()->exists();
    }

    /**
     * Determine if the tag team can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        if ($this->isUnemployed() || $this->hasFutureEmployment() || $this->isReleased()) {
            // throw new CannotBeRetiredException('Tag Team cannot be retired. This Tag Team does not have an active employment.');
            return false;
        }

        if ($this->isRetired()) {
            // throw new CannotBeRetiredException('Tag Team cannot be retired. This Tag Team is retired.');
            return false;
        }

        return true;
    }

    /**
     * Determine if the tag team can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        if (! $this->isRetired()) {
            // throw new CannotBeUnretiredException('Tag Team cannot be unretired. This Tag Team is not retired.');
            return false;
        }

        return true;
    }

    /**
     * Get the suspensions of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function suspensions()
    {
        return $this->morphMany(Suspension::class, 'suspendable');
    }

    /**
     * Get the current suspension of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentSuspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the previous suspensions of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousSuspensions()
    {
        return $this->suspensions()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous suspension of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousSuspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include suspended tag teams.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended($query)
    {
        return $this->whereHas('currentSuspension');
    }

    /**
     * Scope a query to include tag team's current suspension date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrentSuspendedAtDate($query)
    {
        return $query->addSelect(['current_suspended_at' => Suspension::select('started_at')
            ->whereColumn('suspendable_id', $query->qualifyColumn('id'))
            ->where('suspendable_type', $this->getMorphClass())
            ->orderBy('started_at', 'desc')
            ->limit(1),
        ])->withCasts(['current_suspended_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the tag team's current suspension date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentSuspendedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_suspended_at) $direction");
    }

    /**
     * Suspend a tag team.
     *
     * @param  string|null $suspendedAt
     * @return void
     */
    public function suspend($suspendedAt = null)
    {
        throw_unless($this->canBeSuspended(), new CannotBeSuspendedException('Tag Team cannot be reinstated. This Tag Team is not suspended.'));

        $suspendedDate = $suspendedAt ?: now();

        $this->suspensions()->create(['started_at' => $suspendedDate]);
        $this->currentWrestlers->each->suspend($suspendedDate);
        $this->updateStatusAndSave();
    }

    /**
     * Reinstate a tag team.
     *
     * @param  string|null $reinstatedAt
     * @return void
     */
    public function reinstate($reinstatedAt = null)
    {
        throw_unless($this->canBeReinstated(), new CannotBeReinstatedException('Tag Team cannot be reinstated. This Tag Team is not suspended.'));

        $reinstatedDate = $reinstatedAt ?: now();

        $this->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $this->currentWrestlers->each->reinstate($reinstatedDate);
        $this->updateStatusAndSave();
    }

    /**
     * Check to see if the tag team is suspended.
     *
     * @return bool
     */
    public function isSuspended()
    {
        return $this->currentSuspension()->exists();
    }

    /**
     * Determine if the tag team can be suspended.
     *
     * @return bool
     */
    public function canBeSuspended()
    {
        if ($this->isUnemployed() || $this->isReleased() || $this->hasFutureEmployment()) {
            // throw new CannotBeSuspendedException('Tag Team cannot be suspended. This Tag Team does not have an active employment.');
            return false;
        }

        if ($this->isSuspended()) {
            // throw new CannotBeSuspendedException('Tag Team cannot be suspended. This Tag Team is currently suspended.');
            return false;
        }

        if ($this->isRetired()) {
            // throw new CannotBeSuspendedException('Tag Team cannot be suspended. This Tag Team is currently retired.');
            return false;
        }

        return true;
    }

    /**
     * Determine if the tag team can be reinstated.
     *
     * @return bool
     */
    public function canBeReinstated()
    {
        if (! $this->isSuspended()) {
            // throw new CannotBeReinstatedException('Tag Team cannot be reinstated. This Tag Team is not suspended.');
            return false;
        }

        if ($this->currentWrestlers->count() != 2 || ! $this->currentWrestlers->each->canBeReinstated()) {
            // throw new CannotBeReinstatedException('Tag Team cannot be reinstated. This Tag Team does not have two suspended wrestlers.');
            return false;
        }

        return true;
    }

    /**
     * Scope a query to only include bookable tag teams.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBookable($query)
    {
        return $query->where('status', 'bookable');
    }

    /**
     * Check to see if the tag team is bookable.
     *
     * @return bool
     */
    public function isBookable()
    {
        if ($this->isNotInEmployment()) {
            return false;
        }

        if (! $this->partnersAreBookable()) {
            return false;
        }

        return true;
    }

    /**
     * Check to see if the tag team is unbookable.
     *
     * @return bool
     */
    public function isUnbookable()
    {
        return ! $this->isBookable();
    }

    /**
     * Find out if both tag team partners are bookable.
     *
     * @return bool
     */
    public function partnersAreBookable()
    {
        foreach ($this->currentWrestlers as $wrestler) {
            if (! $wrestler->isBookable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update the status for the tag team.
     *
     * @return void
     */
    public function updateStatus()
    {
        if ($this->isCurrentlyEmployed()) {
            if ($this->isSuspended()) {
                $this->status = TagTeamStatus::SUSPENDED;
            } elseif ($this->isBookable()) {
                $this->status = TagTeamStatus::BOOKABLE;
            } elseif ($this->isUnbookable()) {
                $this->status = TagTeamStatus::UNBOOKABLE;
            }
        } elseif ($this->hasFutureEmployment()) {
            $this->status = TagTeamStatus::FUTURE_EMPLOYMENT;
        } elseif ($this->isReleased()) {
            $this->status = TagTeamStatus::RELEASED;
        } elseif ($this->isRetired()) {
            $this->status = TagTeamStatus::RETIRED;
        } else {
            $this->status = TagTeamStatus::UNEMPLOYED;
        }
    }

    /**
     * Updates a tag team's status and saves.
     *
     * @return void
     */
    public function updateStatusAndSave()
    {
        $this->updateStatus();
        $this->save();
    }
}
