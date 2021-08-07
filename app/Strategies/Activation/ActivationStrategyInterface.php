<?php

namespace App\Strategies\Activation;

interface ActivationStrategyInterface
{
    /**
     * Activate an activatable model.
     *
     * @param  string|null $activatedAtDate
     * @return void
     */
    public function activate(string $activatedAtDate = null);
}
