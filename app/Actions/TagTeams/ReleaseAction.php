<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\Roster\TagTeams\TagTeam;
use App\Services\TagTeamReleaseService;
use Illuminate\Support\Carbon;

class ReleaseAction
{
    public function __construct(
        private readonly TagTeamReleaseService $release,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

    /**
     * Release a tag team from employment and end all current relationships.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $releaseDate = null): void
    {
        $this->release->release(
            $tagTeam,
            $releaseDate ?? now(),
            function (TagTeam $lockedTagTeam, Carbon $effectiveDate): void {
                $this->endCurrentRelationships->handle($lockedTagTeam, $effectiveDate);
            },
        );
    }
}
