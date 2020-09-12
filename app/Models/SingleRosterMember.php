<?php

namespace App\Models;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Exceptions\CannotBeEmployedException;
use App\Exceptions\CannotBeInjuredException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeUnretiredException;
use Illuminate\Database\Eloquent\Model;

abstract class SingleRosterMember extends Model
{
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
        return $this->morphOne(Employment::class, 'employable')
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include future employment models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeFutureEmployment($query)
    {
        return $query->whereHas('futureEmployment');
    }

    /**
     * Scope a query to only include employed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeEmployed($query)
    {
        return $query->whereHas('currentEmployment')
                    ->whereDoesntHave('currentSuspension')
                    ->whereDoesntHave('currentInjury');
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
     * Scope a query to order by the models current deactivation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByCurrentDeactivatedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_released_at) $direction");
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
     * Employ a model.
     *
     * @param  Carbon|string $startedAt
     * @return bool
     */
    public function employ($startedAt = null)
    {
        if ($this->canBeEmployed()) {
            $startDate = $startedAt ?? now();

            $this->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);

            return $this->touch();
        }
    }

    /**
     * Release a model.
     *
     * @param  Carbon|string $releasedAt
     * @return bool
     */
    public function release($releasedAt = null)
    {
        if ($this->isSuspended()) {
            $this->reinstate();
        }

        if ($this->isInjured()) {
            $this->clearFromInjury();
        }

        $releaseDate = $releasedAt ?? now();
        $this->currentEmployment()->update(['ended_at' => $releaseDate]);

        return $this->touch();
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
     * Check to see if the model is employed.
     *
     * @return bool
     */
    public function isUnemployed()
    {
        return $this->employments->isEmpty();
    }

    /**
     * Check to see if the model has a future scheduled employment.
     *
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
     * Determine if the model can be employed.
     *
     * @return bool
     */
    public function canBeEmployed()
    {
        if ($this->isCurrentlyEmployed()) {
            throw new CannotBeEmployedException('Entity cannot be employed. This entity is currently employed.');
        }

        if ($this->isRetired()) {
            throw new CannotBeEmployedException('Entity cannot be employed. This entity does not have an active employment.');
        }

        return true;
    }

    /**
     * Determine if the model can be released.
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
     * Get the model's first employment date.
     *
     * @return string|null
     */
    public function getStartedAtAttribute()
    {
        return optional($this->employments->last())->started_at;
    }

    public function retire($retiredAt = null)
    {
        if ($this->canBeRetired()) {
            if ($this->isSuspended()) {
                $this->reinstate();
            }

            if ($this->isInjured()) {
                $this->clearFromInjury();
            }

            $retiredDate = $retiredAt ?: now();
            $this->currentEmployment()->update(['ended_at' => $retiredDate]);

            $this->retirements()->create(['started_at' => $retiredDate]);

            return $this->touch();
        }
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
        return $this->retirements()
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the previous retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousRetirements()
    {
        return $this->retirements()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousRetirement()
    {
        return $this->previousRetirements()
                    ->latest('ended_at')
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
     * Unretire a model.
     *
     * @param  string|null $unretiredAt
     * @return bool
     */
    public function unretire($unretiredAt = null)
    {
        if ($this->canBeUnretired()) {
            $unretiredDate = $unretiredAt ?: now();

            $this->currentRetirement()->update(['ended_at' => $unretiredDate]);
            $this->employ($unretiredAt);

            return $this->touch();
        }
    }

    /**
     * Check to see if the model is retired.
     *
     * @return bool
     */
    public function isRetired()
    {
        return $this->whereHas('currentRetirement')->exists();
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        if ($this->isUnemployed() || $this->isReleased() || $this->hasFutureEmployment()) {
            throw new CannotBeRetiredException('Entity cannot be retired. This entity does not have an active employment.');
        }

        if ($this->isRetired()) {
            throw new CannotBeRetiredException('Entity cannot be retired. This entity is retired.');
        }

        return true;
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        if (! $this->isRetired()) {
            throw new CannotBeUnretiredException('Entity cannot be unretired. This entity is not retired.');
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
     * Get the previous retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousSuspensions()
    {
        return $this->suspensions()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousSuspension()
    {
        return $this->previousSuspensions()
                    ->latest('ended_at')
                    ->limit(1);
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
     * Suspend a model.
     *
     * @param  string|null $suspendedAt
     * @return \App\Models\Suspension
     */
    public function suspend($suspendedAt = null)
    {
        if ($this->canBeSuspended()) {
            $suspensionDate = $suspendedAt ?? now();

            $this->suspensions()->create(['started_at' => $suspensionDate]);

            return $this->touch();
        }
    }

    /**
     * Reinstate a model.
     *
     * @param  string|null $reinstatedAt
     * @return bool
     */
    public function reinstate($reinstatedAt = null)
    {
        if ($this->canBeReinstated()) {
            $reinstatedDate = $reinstatedAt ?: now();

            $this->currentSuspension()->update(['ended_at' => $reinstatedDate]);

            return $this->touch();
        }
    }

    /**
     * @return bool
     */
    public function isSuspended()
    {
        return $this->currentSuspension()->exists();
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeSuspended()
    {
        if ($this->isUnemployed() || $this->isReleased() || $this->hasFutureEmployment()) {
            throw new CannotBeSuspendedException('Entity cannot be suspended. This entity does not have an active employment.');
        }

        if ($this->isSuspended()) {
            throw new CannotBeSuspendedException('Entity cannot be suspended. This entity is currently suspended.');
        }

        if ($this->isRetired()) {
            throw new CannotBeSuspendedException('Entity cannot be suspended. This entity is currently retired.');
        }

        if ($this->isInjured()) {
            throw new CannotBeSuspendedException('Entity cannot be suspended. This entity is currently injured.');
        }

        return true;
    }

    /**
     * Determine if the model can be reinstated.
     *
     * @return bool
     */
    public function canBeReinstated()
    {
        if (! $this->isSuspended()) {
            throw new CannotBeReinstatedException('Entity cannot be reinstated. This entity is not suspended.');
        }

        return true;
    }

    /**
     * Get the current suspension of the model.
     *
     * @return App\Models\Suspension
     */
    public function getCurrentSuspensionAttribute()
    {
        if (! $this->relationLoaded('currentSuspension')) {
            $this->setRelation('currentSuspension', $this->currentSuspension()->get());
        }

        return $this->getRelation('currentSuspension')->first();
    }

    /**
     * Get the previous suspension of the model.
     *
     * @return App\Models\Suspension
     */
    public function getPreviousSuspensionAttribute()
    {
        if (! $this->relationLoaded('previousSuspension')) {
            $this->setRelation('previousSuspension', $this->previousSuspension()->get());
        }

        return $this->getRelation('previousSuspension')->first();
    }

    /**
     * Get the injuries of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function injuries()
    {
        return $this->morphMany(Injury::class, 'injurable');
    }

    /**
     * Get the current injury of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentInjury()
    {
        return $this->morphOne(Injury::class, 'injurable')
                    ->whereNull('ended_at');
    }

    /**
     * Get the previous injuries of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousInjuries()
    {
        return $this->injuries()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousInjury()
    {
        return $this->previousInjuries()
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include injured models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInjured($query)
    {
        return $query->whereHas('currentInjury');
    }

    /**
     * Scope a query to include current injured at dates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithCurrentInjuredAtDate($query)
    {
        return $query->addSelect(['current_injured_at' => Injury::select('started_at')
            ->whereColumn('injurable_id', $query->qualifyColumn('id'))
            ->where('injurable_type', $this->getMorphClass())
            ->orderBy('started_at', 'desc')
            ->limit(1),
        ])->withCasts(['current_injured_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models current injured date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByCurrentInjuredAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_injured_at) $direction");
    }

    /**
     * Injure a model.
     *
     * @param  string|null $injuredAt
     * @return \App\Models\Injury
     */
    public function injure($injuredAt = null)
    {
        if ($this->canBeInjured()) {
            $injuredDate = $injuredAt ?? now();

            $this->injuries()->create(['started_at' => $injuredDate]);

            return $this->touch();
        }
    }

    /**
     * Recover a model.
     *
     * @param  string|null $recoveredAt
     * @return bool
     */
    public function clearFromInjury($recoveredAt = null)
    {
        if ($this->canBeClearedFromInjury()) {
            $recoveryDate = $recoveredAt ?? now();

            $this->currentInjury()->update(['ended_at' => $recoveryDate]);

            return $this->touch();
        }
    }

    /**
     * Check to see if the currently model is injured.
     *
     * @return bool
     */
    public function isInjured()
    {
        return $this->currentInjury()->exists();
    }

    /**
     * Determine if the model can be injured.
     *
     * @return bool
     */
    public function canBeInjured()
    {
        if ($this->isUnemployed() || $this->isReleased() || $this->hasFutureEmployment() || $this->isRetired()) {
            throw new CannotBeInjuredException('Entity cannot be injured. This entity does not have an active employment.');
        }

        if ($this->isInjured()) {
            throw new CannotBeInjuredException('Entity cannot be injured. This entity is currently injured.');
        }

        if ($this->isSuspended()) {
            throw new CannotBeInjuredException('Entity cannot be injured. This entity is currently suspended.');
        }

        return true;
    }

    /**
     * Determine if the model can be cleared from an injury.
     *
     * @return bool
     */
    public function canBeClearedFromInjury()
    {
        if (! $this->isInjured()) {
            throw new CannotBeClearedFromInjuryException('Entity cannot be marked as being recovered from an injury. This entity is not injured.');
        }

        return true;
    }

    /**
     * Get the current injury of the model.
     *
     * @return App\Models\Injury
     */
    public function getCurrentInjuryAttribute()
    {
        if (! $this->relationLoaded('currentInjury')) {
            $this->setRelation('currentInjury', $this->currentInjury()->get());
        }

        return $this->getRelation('currentInjury')->first();
    }

    /**
     * Get the previous injury of the model.
     *
     * @return App\Models\Injury
     */
    public function getPreviousInjuryAttribute()
    {
        if (! $this->relationLoaded('previousInjury')) {
            $this->setRelation('previousInjury', $this->previousInjury()->get());
        }

        return $this->getRelation('previousInjury')->first();
    }
}
