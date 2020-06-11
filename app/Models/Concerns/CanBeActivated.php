<?php

namespace App\Models\Concerns;

use App\Models\Activation;
use App\Traits\HasCachedAttributes;

trait CanBeActivated
{
    public static function bootCanBeActivated()
    {
        if (config('app.debug')) {
            $traits = class_uses_recursive(static::class);

            if (!in_array(HasCachedAttributes::class, $traits)) {
                throw new \LogicException('CanBeActivated trait used without HasCachedAttributes trait');
            }
        }
    }

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
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousActivation()
    {
        return $this->previousActivations()
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include pending activation models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeFutureActivation($query)
    {
        return $query->whereHas('futureActivation');
    }

    /**
     * Scope a query to only include activated models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeActive($query)
    {
        return $query->whereHas('currentActivation');
    }

    /**
     * Scope a query to only include deactivated models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
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
     */
    public function scopeUnactivated($query)
    {
        return $query->whereDoesntHave('activations');
    }

    /**
     * Scope a query to only include unactivated models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithFirstActivatedAtDate($query)
    {
        return $query->addSelect(['first_activated_at' => Activation::select('started_at')
            ->whereColumn('activatable_id', $query->qualifyColumn('id'))
            ->where('activatable_type', $this->getMorphClass())
            ->oldest('started_at')
            ->limit(1)
        ])->withCasts(['first_activated_at' => 'datetime']);
    }

    /**
     * Scope a query to only include unactivated models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithCurrentDeactivatedAtDate($query)
    {
        return $query->addSelect(['current_deactivated_at' => Activation::select('ended_at')
            ->whereColumn('activatable_id', $query->qualifyColumn('id'))
            ->where('activatable_type', $this->getMorphClass())
            ->orderBy('ended_at', 'desc')
            ->limit(1)
        ])->withCasts(['current_deactivated_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models first activation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByFirstActivatedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(first_activated_at) $direction");
    }

    /**
     * Scope a query to order by the models current deactivation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByCurrentDeactivatedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_deactivated_at) $direction");
    }

    /**
     * Activate a model.
     *
     * @param  Carbon|string $startedAt
     * @return bool
     */
    public function activate($startedAt = null)
    {
        $startDate = $startedAt ?? now();

        $this->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);

        return $this->touch();
    }

    /**
     * Deactivate a model.
     *
     * @param  Carbon|string $deactivatedAt
     * @return bool
     */
    public function deactivate($deactivatedAt = null)
    {
        $deactivatedDate = $deactivatedAt ?? now();

        $this->currentActivation()->update(['ended_at' => $deactivatedDate]);

        return $this->touch();
    }

    /**
     * Check to see if the model is activated.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->currentActivation()->exists();
    }

    /**
     * Determine if a model is retired.
     *
     * @return bool
     */
    public function getIsActiveCachedAttribute()
    {
        return $this->status === 'active';
    }

    /**
     * Check to see if the model is activated.
     *
     * @return bool
     */
    public function isUnactivated()
    {
        return $this->activations->isEmpty();
    }

    /**
     * Check to see if the model is activated.
     *
     * @return bool
     */
    public function hasPreviouslyBeenActivated()
    {
        return $this->activations->isNotEmpty();
    }

    /**
     * Check to see if the model has a future scheduled activation.
     *
     * @return bool
     */
    public function hasFutureActivation()
    {
        return $this->futureActivation()->exists();
    }

    /**
     * Check to see if the model has been deactivated.
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
        if ($this->isActive()) {
            return false;
        }

        if ($this->isRetired()) {
            return false;
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
        if ($this->isUnactivated()) {
            return false;
        }

        if ($this->hasFutureActivation()) {
            return false;
        }

        if ($this->isDeactivated()) {
            return false;
        }

        if ($this->isRetired()) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve a introduced at date timestamp.
     *
     * @return string
     */
    public function getActivatedAtAttribute()
    {
        return optional($this->activations->first())->started_at;
    }

    /**
    * Get the current activation of the model.
    *
    * @return App\Models\Activation
    */
    public function getCurrentActivationAttribute()
    {
        if (! $this->relationLoaded('currentActivation')) {
            $this->setRelation('currentActivation', $this->currentActivation()->get());
        }

        return $this->getRelation('currentActivation')->first();
    }

    /**
     * Get the previous activation of the model.
     *
     * @return App\Models\Activation
     */
    public function getPreviousActivationAttribute()
    {
        if (! $this->relationLoaded('previousActivation')) {
            $this->setRelation('previousActivation', $this->previousActivation()->get());
        }

        return $this->getRelation('previousActivation')->first();
    }

    /**
     * Get the previous activation of the model.
     *
     * @return App\Models\Activation
     */
    public function getFutureActivationAttribute()
    {
        if (! $this->relationLoaded('futureActivation')) {
            $this->setRelation('futureActivation', $this->futureActivation()->get());
        }

        return $this->getRelation('futureActivation')->first();
    }
}
