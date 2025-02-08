<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait IsInjurable
{
    public function currentInjury(): HasOne
    {
        return $this->injuries()
            ->whereNull('ended_at')
            ->one();
    }

    public function previousInjuries(): HasMany
    {
        return $this->injuries()
            ->whereNotNull('ended_at');
    }

    public function previousInjury(): HasOne
    {
        return $this->previousInjuries()
            ->one()
            ->ofMany('ended_at', 'max');
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
