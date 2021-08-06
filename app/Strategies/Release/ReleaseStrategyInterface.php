<?php

namespace App\Strategies\Release;

use Carbon\Carbon;

interface ReleaseStrategyInterface
{
    /**
     * Release a releasable model.
     *
     * @param  \Carbon\Carbon|null $releasedAt
     * @return void
     */
    public function release(Carbon $releasedAt = null);
}
