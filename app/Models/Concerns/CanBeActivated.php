<?php

namespace App\Models\Concerns;

use App\Exceptions\CannotBeActivatedException;
use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Activation;

trait CanBeActivated
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
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the first activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function firstActivation()
    {
        return $this->morphOne(Activation::class, 'activatable')
                    ->oldest('started_at')
                    ->limit(1);
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
                    ->limit(1);
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
     * Get the previous activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousActivation()
    {
        return $this->morphOne(Activation::class, 'activatable')
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include future activated models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFutureActivation($query)
    {
        return $query->whereHas('futureActivation');
    }

    /**
     * Scope a query to only include activated models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereHas('currentActivation');
    }

    /**
     * Scope a query to only include inactive models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->whereHas('previousActivation')
                    ->whereDoesntHave('currentActivation')
                    ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to only include unactivated models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnactivated($query)
    {
        return $query->whereDoesntHave('currentActivation')
                    ->orWhereDoesntHave('previousActivations');
    }

    /**
     * Scope a query to include current activation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFirstActivatedAtDate($query)
    {
        return $query->addSelect(['first_activated_at' => Activation::select('started_at')
            ->whereColumn('activatable_id', $query->qualifyColumn('id'))
            ->where('activatable_type', $this->getMorphClass())
            ->oldest('started_at')
            ->limit(1),
        ])->withCasts(['first_activated_at' => 'datetime']);
    }

    /**
     * Scope a query to include current deactivation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrentDeactivatedAtDate($query)
    {
        return $query->addSelect(['current_deactivated_at' => Activation::select('ended_at')
            ->whereColumn('activatable_id', $query->qualifyColumn('id'))
            ->where('activatable_type', $this->getMorphClass())
            ->orderBy('ended_at', 'desc')
            ->limit(1),
        ])->withCasts(['current_deactivated_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models first activation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByFirstActivatedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(first_activated_at) $direction");
    }

    /**
     * Scope a query to order by the models current deactivation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentDeactivatedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_deactivated_at) $direction");
    }

    /**
     * Activate a model.
     *
     * @param  string|null $startedAt
     * @return $this
     */
    public function activate($startedAt = null)
    {
        if ($this->canBeActivated()) {
            $startDate = $startedAt ?? now();

            $this->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);

            return $this->touch();
        }
    }

    /**
     * Deactivate a model.
     *
     * @param  string|null $deactivatedAt
     * @return $this
     */
    public function deactivate($deactivatedAt = null)
    {
        if ($this->canBeDeactivated()) {
            $deactivatedDate = $deactivatedAt ?? now();

            $this->currentActivation()->update(['ended_at' => $deactivatedDate]);

            return $this->touch();
        }
    }

    /**
     * Check to see if the model is active.
     *
     * @return bool
     */
    public function isCurrentlyActive()
    {
        return $this->currentActivation()->exists();
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
     * Check to see if the model is deactivated.
     *
     * @return bool
     */
    public function isDeactivated()
    {
        return $this->previousActivation()->exists() &&
                $this->currentActivation()->doesntExist() &&
                $this->currentRetirement()->doesntExist();
    }

    /**
     * Determine if the model can be activated.
     *
     * @return bool
     */
    public function canBeActivated()
    {
        if ($this->isCurrentlyActive()) {
            throw new CannotBeActivatedException('Entity cannot be activated. This entity is active.');
        }

        if ($this->isRetired()) {
            throw new CannotBeActivatedException('Entity cannot be activated. This entity is retired.');
        }

        return true;
    }

    /**
     * Determine if the model can be deactivated.
     *
     * @return bool
     */
    public function canBeDeactivated()
    {
        if ($this->isUnactivated() || $this->hasFutureActivation()) {
            throw new CannotBeDeactivatedException('Entity cannot be deactivated. This entity has not been activated.');
        }

        if ($this->isDeactivated()) {
            throw new CannotBeDeactivatedException('Entity cannot be deactivated. This entity is deactivated.');
        }

        if ($this->isRetired()) {
            throw new CannotBeDeactivatedException('Entity cannot be deactivated. This entity has not been retired.');
        }

        return true;
    }

    /**
     * Retrieve an activation date.
     *
     * @return string|null
     */
    public function getActivatedAtAttribute()
    {
        return optional($this->activations->first())->started_at;
    }
}
