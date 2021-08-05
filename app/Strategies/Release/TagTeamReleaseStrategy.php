<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeSuspendedException;

class TagTeamReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
{
    public function release($model)
    {
        throw_unless($model->canBeSuspended(), new CannotBeSuspendedException());
    }
}
