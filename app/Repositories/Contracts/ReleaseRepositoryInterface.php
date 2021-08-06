<?php

namespace App\Repositories\Contracts;

use App\Models\Contracts\Releasable;

interface ReleaseRepositoryInterface
{
    /**
     * Release a releasable.
     *
     * @param  \App\Models\Contracts\Releasable $releasable
     * @param  string $releaseDate
     * @return \App\Models\Contracts\Releasable $releasable
     */
    public function release(Releasable $releasable, string $releaseDate);
}
