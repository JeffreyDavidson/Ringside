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
        Concerns\CanBeBooked,
        Concerns\Unguarded;

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
     * Get the wrestlers belonging to the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wrestlers()
    {
        return $this->belongsToMany(Wrestler::class, 'tag_team_wrestler')
                    ->withTimestamps();
    }

    /**
     * Get all current wrestlers that are members of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function currentWrestlers()
    {
        return $this->wrestlers()
                    ->where('started_at', '<=', now())
                    ->whereNull('left_at')
                    ->limit(2);
    }

    /**
     * Get all current wrestlers that are members of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousWrestlers()
    {
        return $this->wrestlers()
                    ->whereNotNull('left_at');
    }

    /**
     * Get the stables the tag team are members of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stables()
    {
        return $this->morphToMany(Stable::class, 'member');
    }

    /**
     * Get the current stable of the tag team.
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
     * Get the previous stables the tag team has been a part of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function previousStables()
    {
        return $this->stalbes()
                    ->whereNotNull('left_at');
    }

    /**
     * Get the combined weight of both wrestlers in a tag team.
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
     * Check to see if the model is employed.
     *
     * @return bool
     */
    public function isCurrentlyEmployed()
    {
        return $this->currentEmployment()->exists();
    }

    /**
     * Determine if the model can be reinstated.
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
     * Check to see if the model is employed.
     *
     * @return bool
     */
    public function isUnemployed()
    {
        return $this->employments->isEmpty();
    }

    /**
     * Employ a tag team.
     *
     * @return bool
     */
    public function employ($startAtDate = null)
    {
        if ($this->canBeEmployed()) {
            $startAtDate = $startAtDate ?? now();

            $this->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startAtDate]);
            $this->currentWrestlers->each->employ($startAtDate);

            return $this->touch();
        }
    }

    /**
     * Determine if the model can be retired.
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
     * Retire a tag team.
     *
     * @return \App\Models\Retirement
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
     * @return bool
     */
    public function unretire()
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
     * Determine if the model can be retired.
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
     * Get the suspensions of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function suspensions()
    {
        return $this->morphMany(Suspension::class, 'suspendable');
    }

    /**
     * Get the current suspension of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentSuspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')
                    ->whereNull('ended_at');
    }

    /**
     * Determine if the model can be reinstated.
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
     * Suspend a tag team.
     *
     * @return \App\Models\Suspension
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
     * Determine if the model can be reinstated.
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
     * Reinstate a tag team.
     *
     * @return bool
     */
    public function reinstate($reinstatedAt = null)
    {
        if ($this->canBeReinstated()) {
            $reinstatedDate = $reinstatedAt ?: now();

            $this->currentSuspension()->update(['ended_at' => $reinstatedDate]);
            $this->currentWrestlers->each->reinstate($reinstatedDate);

            return $this->touch();
        }
    }

    /**
     * @return bool
     */
    public function isBookable()
    {
        if ($this->currentEmployment()->doesntExist()) {
            return false;
        }

        if ($this->currentSuspension()->exists()) {
            return false;
        }

        if ($this->currentRetirement()->exists()) {
            return false;
        }

        if (! $this->currentWrestlers->each->isBookable()) {
            return false;
        }

        return true;
    }

    /**
     * Get all of the employments of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function employments()
    {
        return $this->morphMany(Employment::class, 'employable');
    }

    /**
     * Scope a query to only include employed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeEmployed($query)
    {
        return $query->whereHas('currentEmployment')
                    ->whereDoesntHave('currentSuspension');
    }

    /**
     * Get the current employment of the model.
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
     * Get the future employment of the model.
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
     * Get the previous employments of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousEmployments()
    {
        return $this->employments()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousEmployment()
    {
        return $this->previousEmployments()
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * @return bool
     */
    public function hasFutureEmployment()
    {
        return $this->futureEmployment()->exists();
    }

    /**
     * Check to see if the model has been released.
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
     * Scope a query to only include future employment models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePendingEmployment($query)
    {
        return $query->whereHas('futureEmployment');
    }

    /**
     * Scope a query to only include released models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeReleased($query)
    {
        return $query->whereHas('previousEmployment')
                    ->whereDoesntHave('currentEmployment')
                    ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to only include unemployed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeUnemployed($query)
    {
        return $query->whereDoesntHave('currentEmployment');
    }

    /**
     * Scope a query to only include unemployed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
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
     * Scope a query to order by the models first activation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByFirstEmployedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(first_employed_at) $direction");
    }

    /**
     * Scope a query to only include released models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
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
     * Get the retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements()
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the model.
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
     * Scope a query to only include retired models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRetired($query)
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Scope a query to only include unemployed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
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
     * Scope a query to order by the models current retirement date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByCurrentRetiredAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_retired_at) $direction");
    }

    /**
     * Check to see if the model is retired.
     *
     * @return bool
     */
    public function isRetired()
    {
        return $this->currentRetirement()->exists();
    }

    /**
     * Scope a query to only include suspended models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended($query)
    {
        return $this->whereHas('currentSuspension');
    }

    /**
     * Scope a query to include current suspended at dates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
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
     * Scope a query to order by the models current suspension date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByCurrentSuspendedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_suspended_at) $direction");
    }

    /**
     * @return bool
     */
    public function isSuspended()
    {
        return $this->currentSuspension()->exists();
    }
}
