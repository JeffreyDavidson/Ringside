<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Models\Roster\Referees\Referee;
use App\Services\Roster\Individuals\IndividualReleaseService;
use Illuminate\Support\Carbon;

class ReleaseAction
{
    public function __construct(
        private readonly IndividualReleaseService $release,
    ) {}

    /**
     * Release a referee from employment.
     */
    public function handle(Referee $referee, ?Carbon $releaseDate = null): void
    {
        $this->release->release($referee, $releaseDate ?? now());
    }
}
