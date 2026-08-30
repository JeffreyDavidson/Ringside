<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\TagTeams\TagTeamUnretirementService;
use Illuminate\Support\Carbon;

class UnretireAction
{
    public function __construct(
        private readonly TagTeamUnretirementService $unretirement,
        private readonly UnretireCurrentMembersAction $unretireCurrentMembers,
        private readonly EmployAction $employ,
    ) {}

    /**
     * Unretire a tag team and optionally return it to employment.
     */
    public function handle(
        TagTeam $tagTeam,
        ?Carbon $unretiredDate = null,
        bool $unretireMembers = true,
        bool $employImmediately = true,
        bool $requireAvailablePartners = true
    ): void {
        $this->unretirement->unretire(
            $tagTeam,
            $unretiredDate ?? now(),
            $requireAvailablePartners,
            function (TagTeam $lockedTagTeam, Carbon $effectiveDate) use ($unretireMembers, $employImmediately): void {
                if ($unretireMembers) {
                    $this->unretireCurrentMembers->handle($lockedTagTeam, $effectiveDate);
                }

                if ($employImmediately && ! $lockedTagTeam->currentEmployment()->exists() && $lockedTagTeam->currentWrestlers()->exists()) {
                    $this->employ->handle($lockedTagTeam, $effectiveDate);
                }
            },
        );
    }
}
