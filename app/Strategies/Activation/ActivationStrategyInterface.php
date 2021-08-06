<?php

namespace App\Strategies\Activation;

interface ActivationStrategyInterface
{
    /**
     * Activate an activatable model.
     *
     * @param  string|null $startedAt
     * @return void
     */
    public function activate(string $startedAt = null);
}
