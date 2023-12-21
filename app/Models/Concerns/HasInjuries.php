<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Injury;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasInjuries
{
    /**
     * Get the injuries of the model.
     *
     * @return MorphMany<Injury>
     */
    public function injuries(): MorphMany
    {
        return $this->morphMany(Injury::class, 'injurable');
    }

    /**
     * Get the current injury of the model.
     *
     * @return MorphOne<Injury>
     */
    public function currentInjury(): MorphOne
    {
        return $this->morphOne(Injury::class, 'injurable')
            ->whereNull('ended_at')
            ->limit(1);
    }

    /**
     * Get the previous injuries of the model.
     *
     * @return MorphMany<Injury>
     */
    public function previousInjuries(): MorphMany
    {
        return $this->injuries()
            ->whereNotNull('ended_at');
    }

    /**
     * Get the previous injury of the model.
     *
     * @return MorphOne<Injury>
     */
    public function previousInjury(): MorphOne
    {
        return $this->morphOne(Injury::class, 'injurable')
            ->latest('ended_at')
            ->limit(1);
    }

    /**
     * Check to see if the model is injured.
     */
    public function isInjured(): bool
    {
        return $this->currentInjury()->exists();
    }

    /**
     * Check to see if the model has been injured.
     */
    public function hasInjuries(): bool
    {
        return $this->injuries()->count() > 0;
    }
}
