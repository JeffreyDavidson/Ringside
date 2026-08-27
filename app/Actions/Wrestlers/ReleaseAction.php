<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\IndividualReleaseService;
use Illuminate\Support\Carbon;

class ReleaseAction
{
    public function __construct(
        private readonly IndividualReleaseService $release,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

    /**
     * Release a wrestler from employment and end all current relationships.
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        $this->release->release(
            $wrestler,
            $releaseDate ?? now(),
            function (Wrestler $lockedWrestler, Carbon $effectiveDate): void {
                $this->endCurrentRelationships->handle($lockedWrestler, $effectiveDate);
            },
        );
    }
}
