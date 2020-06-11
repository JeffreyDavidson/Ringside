<?php

namespace App\Models\Concerns;

use App\Models\Injury;
use App\Traits\HasCachedAttributes;

trait CanBeInjured
{
    public static function bootCanBeInjured()
    {
        if (config('app.debug')) {
            $traits = class_uses_recursive(static::class);

            if (!in_array(HasCachedAttributes::class, $traits)) {
                throw new \LogicException('CanBeRetired trait used without HasCachedAttributes trait');
            }
        }
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
     * Injure a model.
     *
     * @param  string|null $injuredAt
     * @return \App\Models\Injury
     */
    public function injure($injuredAt = null)
    {
        $injuredDate = $injuredAt ?? now();

        $this->injuries()->create(['started_at' => $injuredDate]);

        return $this->touch();
    }

    /**
     * Recover a model.
     *
     * @param  string|null $recoveredAt
     * @return bool
     */
    public function clearFromInjury($recoveredAt = null)
    {
        $recoveryDate = $recoveredAt ?? now();

        $this->currentInjury()->update(['ended_at' => $recoveryDate]);

        return $this->touch();
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
        if ($this->isUnemployed()) {
            return false;
        }

        if ($this->isReleased()) {
            return false;
        }

        if ($this->hasFutureEmployment()) {
            return false;
        }

        if ($this->isInjured()) {
            return false;
        }

        if ($this->isRetired()) {
            return false;
        }

        if ($this->isSuspended()) {
            return false;
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
            return false;
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
