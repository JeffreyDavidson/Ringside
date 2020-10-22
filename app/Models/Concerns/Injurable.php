<?php

namespace App\Models\Concerns;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Exceptions\CannotBeInjuredException;
use App\Models\Injury;
use Carbon\Carbon;

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
    public function currentInjury()
    {
        return $this->morphOne(Injury::class, 'injurable')
                    ->whereNull('ended_at')
                    ->limit(1);
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
     * Get the previous injury of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousInjury()
    {
        return $this->morphOne(Injury::class, 'injurable')
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
     * Scope a query to include current injured date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrentInjuredAtDate($query)
    {
        return $query->addSelect(['current_injured_at' => Injury::select('started_at')
            ->whereColumn('injurable_id', $query->qualifyColumn('id'))
            ->where('injurable_type', $this->getMorphClass())
            ->latest('started_at')
            ->limit(1),
        ])->withCasts(['current_injured_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current injured date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string|null $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentInjuredAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_injured_at) $direction");
    }

    /**
     * Injure a model.
     *
     * @param  string|null $injuredAt
     * @return void
     */
    public function injure($injuredAt = null)
    {
        throw_unless($this->canBeInjured(), new CannotBeInjuredException);

        $injuredDate = Carbon::parse($injuredAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $this->injuries()->create(['started_at' => $injuredDate]);
        $this->updateStatusAndSave();
    }

    /**
     * Clear a model from an injury.
     *
     * @param  string|null $recoveredAt
     * @return void
     */
    public function clearFromInjury($recoveredAt = null)
    {
        throw_unless($this->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = Carbon::parse($recoveredAt)->toDateTImeString('minute') ?? now()->toDateTimeString('minute');

        $this->currentInjury()->update(['ended_at' => $recoveryDate]);
        $this->updateStatusAndSave();
    }

    /**
     * Check to see if the model is injured.
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
        if ($this->isNotInEmployment()) {
            return false;
        }

        if ($this->isInjured()) {
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
}
