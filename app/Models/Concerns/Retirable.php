<?php

namespace App\Models\Concerns;

use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Retirement;
use Carbon\Carbon;

trait Retirable
{
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
     * Get the previous retirement of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousRetirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')
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
        return $query->whereHas('currentRetirement');
    }

    /**
     * Scope a query to include current retirement date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrentRetiredAtDate($query)
    {
        return $query->addSelect(['current_retired_at' => Retirement::select('started_at')
            ->whereColumn('retiree_id', $this->getTable().'.id')
            ->where('retiree_type', $this->getMorphClass())
            ->latest('started_at')
            ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current retirement date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentRetiredAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_retired_at) $direction");
    }

    /**
     * Retire a model.
     *
     * @param  string|null $retiredAt
     * @return void
     */
    public function retire($retiredAt = null)
    {
        throw_unless($this->canBeReleased(), new CannotBeRetiredException);

        if ($this->isSuspended()) {
            $this->reinstate();
        }

        if ($this->isInjured()) {
            $this->clearFromInjury();
        }

        $retiredDate = Carbon::parse($retiredAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $this->currentEmployment()->update(['ended_at' => $retiredDate]);
        $this->retirements()->create(['started_at' => $retiredDate]);
        $this->updateStatusAndSave();
    }

    /**
     * Unretire a model.
     *
     * @param  string|null $unretiredAt
     * @return void
     */
    public function unretire($unretiredAt = null)
    {
        throw_unless($this->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = Carbon::parse($unretiredAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $this->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $this->employments()->create(['started_at' => $unretiredDate]);
        $this->updateStatusAndSave();
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
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        if ($this->isNotInEmployment()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the model can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        if (! $this->isRetired()) {
            return false;
        }

        return true;
    }
}
