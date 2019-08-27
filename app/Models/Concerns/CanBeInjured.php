<?php

namespace App\Models\Concerns;

use App\Models\Injury;

trait CanBeInjured
{
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
    public function injury()
    {
        return $this->morphOne(Injury::class, 'injurable')->whereNull('ended_at');
    }

    /**
     * Determine if a model is injured.
     *
     * @return bool
     */
    public function getIsInjuredAttribute()
    {
        return $this->status === 'injured';
    }

    /**
     * Scope a query to only include injured models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInjured($query)
    {
        return $query->where('status', 'injured');
    }

    /**
     * Injure a model.
     *
     * @return \App\Models\Injury
     */
    public function injure()
    {
        $this->injuries()->create(['started_at' => now()]);
    }

    /**
     * Recover a model.
     *
     * @return bool
     */
    public function recover()
    {
        $this->injury()->update(['ended_at' => now()]);
    }
}
