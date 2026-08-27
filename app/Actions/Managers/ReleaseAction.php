<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Roster\Managers\Manager;
use App\Services\IndividualReleaseService;
use Illuminate\Support\Carbon;

class ReleaseAction
{
    public function __construct(
        private readonly IndividualReleaseService $release,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

    /**
     * Release a manager from employment and end all current relationships.
     */
    public function handle(Manager $manager, ?Carbon $releaseDate = null): void
    {
        $this->release->release(
            $manager,
            $releaseDate ?? now(),
            function (Manager $lockedManager, Carbon $effectiveDate): void {
                $this->endCurrentRelationships->handle($lockedManager, $effectiveDate);
            },
        );
    }
}
