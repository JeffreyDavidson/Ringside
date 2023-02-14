<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait Deactivations
{
    /**
     * Check to see if the model is deactivated.
     */
    public function isDeactivated(): bool
    {
        return $this->previousActivation()->exists()
                && $this->currentActivation()->doesntExist()
                && $this->futureActivation()->doesntExist()
                && $this->currentRetirement()->doesntExist();
    }

    public function canBeDeactivated()
    {
        if ($this->isCurrentlyActivated()) {
            return true;
        }

        return false;
    }

    /**
     * Check to see if the model is not in activation.
     */
    public function isNotInActivation(): bool
    {
        return $this->isNotActivation() || $this->isDeactivated() || $this->hasFutureActivation() || $this->isRetired();
    }
}
