<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Injury;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasInjuries
{
    abstract public function injuries(): HasMany;

    /**
     * @return HasOne<Injury, $this>
     */
    public function currentInjury(): HasOne
    {
        return $this->injuries()
            ->whereNull('ended_at')
            ->latestOfMany();
    }

    /**
     * @return HasMany<Injury, $this>
     */
    public function previousInjuries(): HasMany
    {
        return $this->injuries()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<Injury, $this>
     */
    public function previousInjury(): HasOne
    {
        return $this->previousInjuries()
            ->latestOfMany();
    }

    public function isInjured(): bool
    {
        return $this->currentInjury()->exists();
    }

    public function hasInjuries(): bool
    {
        return $this->injuries()->count() > 0;
    }
}
