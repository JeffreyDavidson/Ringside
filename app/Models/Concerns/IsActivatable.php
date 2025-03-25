<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @phpstan-require-implements \App\Models\Contracts\Activatable
 */
trait IsActivatable
{
    public function currentActivation(): HasOne
    {
        return $this->activations()
            ->whereNull('ended_at')
            ->one();
    }

    public function futureActivation(): HasOne
    {
        return $this->activations()
            ->whereNull('ended_at')
            ->where('started_at', '>', now())
            ->one();
    }

    public function previousActivations(): HasMany
    {
        return $this->activations()
            ->whereNotNull('ended_at');
    }

    public function previousActivation(): HasOne
    {
        return $this->previousActivations()
            ->latest('ended_at')
            ->one();
    }

    public function firstActivation(): HasOne
    {
        return $this->activations()
            ->one()
            ->ofMany('started_at', 'min');
    }

    public function hasActivations(): bool
    {
        return $this->activations()->count() > 0;
    }

    public function isCurrentlyActivated(): bool
    {
        return $this->currentActivation()->exists();
    }

    public function hasFutureActivation(): bool
    {
        return $this->futureActivation()->exists();
    }

    public function isNotInActivation(): bool
    {
        return $this->isDeactivated() || $this->hasFutureActivation() || $this->isRetired();
    }

    public function isUnactivated(): bool
    {
        return $this->activations()->count() === 0;
    }

    public function isDeactivated(): bool
    {
        return $this->previousActivation()->exists()
            && $this->futureActivation()->doesntExist()
            && $this->currentActivation()->doesntExist()
            && $this->currentRetirement()->doesntExist();
    }

    public function activatedOn(Carbon $activationDate): bool
    {
        return $this->currentActivation ? $this->currentActivation->started_at->eq($activationDate) : false;
    }

    public function getFormattedFirstActivation(): string
    {
        return $this->hasActivations() ? ($this->firstActivation?->started_at->format('Y-m-d') ?? 'TBD') : 'TBD';
    }
}
