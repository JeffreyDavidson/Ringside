<?php

namespace App\Strategies\Release;

interface ReleaseStrategyInterface
{
    /**
     * Release a releasable model.
     *
     * @param  string|null $releaseDate
     * @return void
     */
    public function release(string $releaseDate = null);
}
