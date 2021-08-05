<?php

namespace App\Strategies\Deactivation;

interface DeactivationStrategyInterface
{
    public function deactivate($model);
}
