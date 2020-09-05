<?php

namespace App\Models;

use App\Traits\HasCachedAttributes;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\CannotBeInjuredException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeClearedFromInjuryException;

abstract class SingleRosterMember extends Model
{
    use HasCachedAttributes,
        Concerns\CanBeRetired,
        Concerns\CanBeEmployed;

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
