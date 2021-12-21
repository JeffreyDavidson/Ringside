<?php

namespace App\Models\Concerns;

use App\Models\Activation;
use Illuminate\Database\Eloquent\Builder;

trait Activatable
{
    /**
     * Get all of the activations of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function activations()
    {
        return $this->morphMany(Activation::class, 'activatable');
    }

    /**
     * Get the current activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentActivation()
    {
        return $this->morphOne(Activation::class, 'activatable')
                        ->where('started_at', '<=', now())
                        ->where('ended_at', '=', null)
                        ->latestOfMany();
    }

    /**
     * Get the first activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function firstActivation()
    {
        return $this->morphOne(Activation::class, 'activatable')
                    ->oldestOfMany('started_at');
    }

    /**
     * Get the future activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function futureActivation()
    {
        return $this->morphOne(Activation::class, 'activatable')
                    ->where('started_at', '>', now())
                    ->whereNull('ended_at')
                    ->latestOfMany();
    }

    /**
     * Get the previous activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousActivation()
    {
        return $this->morphOne(Activation::class, 'activatable')
                    ->latest('ended_at')
                    ->oldestOfMany();
    }

    /**
     * Get the previous activations of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousActivations()
    {
        return $this->activations()
                    ->whereNotNull('ended_at');
    }

    /**
     * Scope a query to only include active models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->whereHas('currentActivation');
    }

    /**
     * Scope a query to only include future activated models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFutureActivation(Builder $query)
    {
        return $query->whereHas('futureActivation');
    }

    /**
     * Scope a query to only include inactive models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive(Builder $query)
    {
        return $query->whereHas('previousActivation')
                    ->whereDoesntHave('futureActivation')
                    ->whereDoesntHave('currentActivation')
                    ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to only include inactive models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnactivated(Builder $query)
    {
        return $query->whereDoesntHave('activations');
    }

    /**
     * Scope a query to include current activation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFirstActivatedAtDate(Builder $query)
    {
        return $query->addSelect(['first_activated_at' => Activation::select('started_at')
            ->whereColumn('activatable_id', $query->qualifyColumn('id'))
            ->where('activatable_type', $this->getMorphClass())
            ->oldest('started_at')
            ->limit(1),
        ])->withCasts(['first_activated_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models first activation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByFirstActivatedAtDate(Builder $query, string $direction = 'asc')
    {
        return $query->orderByRaw("DATE(first_activated_at) $direction");
    }

    /**
     * Check to see if the model is currently active.
     *
     * @return bool
     */
    public function isCurrentlyActivated()
    {
        return $this->currentActivation()->exists();
    }

    /**
     * Check to see if the model has been activated.
     *
     * @return bool
     */
    public function hasActivations()
    {
        return $this->activations()->count() > 0;
    }

    /**
     * Check to see if the model is unactivated.
     *
     * @return bool
     */
    public function isUnactivated()
    {
        return $this->activations()->count() === 0;
    }

    /**
     * Check to see if the model has a future activation.
     *
     * @return bool
     */
    public function hasFutureActivation()
    {
        return $this->futureActivation()->exists();
    }

    /**
     * Determine if the model can be activated.
     *
     * @return bool
     */
    public function canBeActivated()
    {
        if ($this->isCurrentlyActivated()) {
            // throw new CannotBeActivatedException('Entity cannot be activated. This entity is active.');
            return false;
        }

        if ($this->isRetired()) {
            // throw new CannotBeActivatedException('Entity cannot be activated. This entity is retired.');
            return false;
        }

        return true;
    }

    /**
     * Retrieve the model's first activation date.
     *
     * @return string|null
     */
    public function getActivatedAtAttribute()
    {
        return $this->activations->first()?->started_at;
    }

    /**
     * Check to see if the model is not in activation.
     *
     * @return bool
     */
    public function isNotActivation()
    {
        return $this->isDeactivated() || $this->hasFutureActivation() || $this->isRetired();
    }

    /**
     * Get the model's first activation date.
     *
     * @param  string $activationDate
     * @return bool
     */
    public function activatedOn(string $activationDate)
    {
        return $this->activations->last()->started_at->ne($activationDate);
    }

    /**
     * Check to see if activatable can have their start date changed.
     *
     * @return bool
     */
    public function canHaveActivationStartDateChanged()
    {
        if ($this->isUnactivated() || $this->hasFutureActivation()) {
            return true;
        }

        return false;
    }
}
