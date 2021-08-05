<?php

namespace App\Strategies\Deactivation;

use App\Exceptions\CannotBeDeactivatedException;

class BaseDeactivationStrategy implements DeactivationStrategyInterface
{
    public function deactivate($model)
    {
        throw_unless($model->canBeDeactivated(), new CannotBeDeactivatedException);

        $model->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $startedAt ?? now()]);
        $model->updateStatusAndSave();
    }
}
