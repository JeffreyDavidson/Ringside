<?php

namespace App\Traits;

use App\Models\Injury;

trait Injurable
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
     * Get the previous injuries of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousInjury()
    {
        return $this->morphOne(Injury::class, 'injurable')->whereNotNull('ended_at')->latest('started_at');
    }

    /**
     * Injure the wrestler.
     *
     * @return void
     */
    public function injure()
    {
        $this->injuries()->create(['started_at' => today()]);
    }

    /**
     * Check to see if the model is injured.
     *
     * @return bool
     */
    public function isInjured()
    {
        return $this->injuries()->whereNull('ended_at')->exists();
    }

    /**
     * Recover the injured model.
     *
     * @return void
     */
    public function recover()
    {
        $this->injury()->update(['ended_at' => today()]);
    }

    /**
     * Scope a query to only include injured models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInjured($query)
    {
        return $query->whereHas('injuries', function ($query) {
            $query->whereNull('ended_at');
        });
    }
}
