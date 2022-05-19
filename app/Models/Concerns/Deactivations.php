<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait Deactivations
{
    /**
     * Check to see if the model is deactivated.
     *
     * @return bool
     */
    public function isDeactivated()
    {
        return $this->previousActivation()->exists()
                && $this->currentActivation()->doesntExist()
                && $this->futureActivation()->doesntExist()
                && $this->currentRetirement()->doesntExist();
    }

    /**
     * Determine if the stable can be deactivated.
     *
     * @return bool
     */
    public function canBeDeactivated()
    {
        if ($this->isCurrentlyActivated()) {
            return true;
        }

        return false;
    }

    /**
     * Check to see if the model is not in activation.
     *
     * @return bool
     */
    public function isNotInActivation()
    {
        return $this->isNotActivation() || $this->isDeactivated() || $this->hasFutureActivation() || $this->isRetired();
    }
}
