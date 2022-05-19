<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait Competable
{
    /**
     * Check to see if the model can be competed for.
     *
     * @return bool
     */
    public function isCompetable()
    {
        if ($this->isNotActivation() || $this->isDeactivated() || $this->isRetired() || $this->hasFutureActivation()) {
            return false;
        }

        return true;
    }
}
