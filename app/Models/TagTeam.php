<?php

namespace App\Models;

use App\Enums\TagTeamStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeUnretiredException;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends Model
{
    use SoftDeletes,
        HasFactory,
        Concerns\Unguarded;

    /**
     * The number of the wrestlers allowed on a tag team.
     *
     * @var int
     */
    const MAX_WRESTLERS_COUNT = 2;

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
     * Get the stables the tag team have been members of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stables()
    {
        return $this->morphToMany(Stable::class, 'member');
    }

    /**
     * Get the current stable the tag team is a member of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function currentStable()
    {
        return $this->stables()
                    ->whereNull('left_at')
                    ->limit(1);
    }

    /**
     * Get the previous stables the tag team has been a member of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function previousStables()
    {
        return $this->stalbes()
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
        if ($this->canBeEmployed()) {
            $startAtDate = $startAtDate ?? now();

            $this->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startAtDate]);

            if ($this->currentWrestlers->every->isUnemployed() || $this->currentWrestlers->every->hasFutureEmployment() || $this->currentWrestlers->every->isRetired()) {
                $this->currentWrestlers->each->employ($startAtDate);
            }

            return $this->touch();
        }
    }

    /**
     * Release a tag team.
     *
     * @param  string|null $releasedAt
     * @return $this
     */
    public function release($releasedAt = null)
    {
        return $this;
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
            throw new CannotBeEmployedException('Tag Team cannot be employed. This Tag Team does not have an active employment.');
        }

        if ($this->currentWrestlers->count() != 2) {
            throw new CannotBeEmployedException('Tag Team cannot be employed. This Tag Team does not have two wrestlers.');
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
        if (! $this->isCurrentlyEmployed() || $this->hasFutureEmployment() || $this->isReleased() || $this->isRetired()) {
            throw new CannotBeEmployedException('Entity cannot be released. This entity does not have an active employment.');
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
        if ($this->canBeRetired()) {
            $retiredDate = $retiredAt ?: now();

            if ($this->is_suspended) {
                $this->reinstate($retiredAt);
                $this->currentWrestlers->each->reinstate($retiredAt);
            }

            $this->currentEmployment()->update(['ended_at' => $retiredDate]);
            $this->currentWrestlers()->each(function ($wrestler) use ($retiredDate) {
                $wrestler->currentEmployment()->update(['ended_at' => $retiredDate]);
            });

            $this->retirements()->create(['started_at' => $retiredDate]);
            $this->currentWrestlers->each->retire($retiredDate);

            return $this->touch();
        }
    }

    /**
     * Unretire a tag team.
     *
     * @param  string|null $unretiredAt
     * @return $this
     */
    public function unretire($unretiredAt = null)
    {
        if ($this->canBeUnretired()) {
            $dateRetired = $this->currentRetirement->started_at;

            $this->currentRetirement()->update(['ended_at' => now()]);

            $this->currentWrestlers()
                ->whereHas('currentRetirement', function ($query) use ($dateRetired) {
                    $query->whereDate('started_at', $dateRetired);
                })
                ->get()
                ->each
                ->unretire();

            return $this->touch();
        }
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
            throw new CannotBeRetiredException('Tag Team cannot be retired. This Tag Team does not have an active employment.');
        }

        if ($this->isRetired()) {
            throw new CannotBeRetiredException('Tag Team cannot be retired. This Tag Team is retired.');
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
            throw new CannotBeUnretiredException('Tag Team cannot be unretired. This Tag Team is not retired.');
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
     * @return $this
     */
    public function suspend($suspendedAt = null)
    {
        if ($this->canBeSuspended()) {
            $suspendedDate = $suspendedAt ?: now();

            $this->suspensions()->create(['started_at' => $suspendedDate]);
            $this->currentWrestlers->each->suspend($suspendedDate);

            return $this->touch();
        }
    }

    /**
     * Reinstate a tag team.
     *
     * @param  string|null $reinstatedAt
     * @return $this
     */
    public function reinstate($reinstatedAt = null)
    {
        if ($this->canBeReinstated()) {
            $reinstatedDate = $reinstatedAt ?: now();

            $this->currentSuspension()->update(['ended_at' => $reinstatedDate]);
            $this->currentWrestlers->every->reinstate($reinstatedDate);

            return $this->touch();
        }
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
            throw new CannotBeSuspendedException('Tag Team cannot be suspended. This Tag Team does not have an active employment.');
        }

        if ($this->isSuspended()) {
            throw new CannotBeSuspendedException('Tag Team cannot be suspended. This Tag Team is currently suspended.');
        }

        if ($this->isRetired()) {
            throw new CannotBeSuspendedException('Tag Team cannot be suspended. This Tag Team is currently retired.');
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
            throw new CannotBeReinstatedException('Tag Team cannot be reinstated. This Tag Team is not suspended.');
        }

        if ($this->currentWrestlers->count() != 2 || ! $this->currentWrestlers->each->canBeReinstated()) {
            throw new CannotBeReinstatedException('Tag Team cannot be reinstated. This Tag Team does not have two suspended wrestlers.');
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
        if ($this->isUnemployed() || $this->isSuspended() || $this->isRetired() || $this->hasFutureEmployment()) {
            return false;
        }

        if (! $this->currentWrestlers->each->isBookable()) {
            return false;
        }

        return true;
    }
}
