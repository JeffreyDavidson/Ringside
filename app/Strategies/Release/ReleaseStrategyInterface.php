<?php

namespace App\Strategies\Release;

interface ReleaseStrategyInterface
{
    /**
     * Release a releasable model.
     *
     * @param  string|null $releasedAt
     * @return void
     */
    public function release(string $releasedAt = null);
}
