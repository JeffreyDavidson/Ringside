<?php

namespace App\Strategies\Release;

use Carbon\Carbon;

interface ReleaseStrategyInterface
{
    public function release(Carbon $releasedAt = null);
}
