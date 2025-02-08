<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait IsRetirable
{
    public function currentRetirement(): HasOne
    {
        return $this->retirements()
            ->whereNull('ended_at')
            ->one();
    }

    public function previousRetirements(): HasMany
    {
        return $this->retirements()
            ->whereNotNull('ended_at');
    }

    public function previousRetirement(): HasOne
    {
        return $this->previousRetirements()
            ->one()
            ->ofMany('ended_at', 'max');
    }

    public function isRetired(): bool
    {
        return $this->currentRetirement()->exists();
    }

    public function hasRetirements(): bool
    {
        return $this->retirements()->count() > 0;
    }
}
