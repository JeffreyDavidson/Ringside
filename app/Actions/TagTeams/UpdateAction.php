<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Data\TagTeams\TagTeamData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\TagTeams\TagTeamMembershipService;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected TagTeamMembershipService $membershipService,
        protected EmployAction $employAction,
        protected EmployCurrentWrestlersAction $employCurrentWrestlersAction,
        protected EmployCurrentManagersAction $employCurrentManagersAction,
    ) {}

    /**
     * Update a tag team while preserving its relationship history.
     */
    public function handle(TagTeam $tagTeam, TagTeamData $tagTeamData): TagTeam
    {
        return DB::transaction(function () use ($tagTeam, $tagTeamData): TagTeam {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $lockedTagTeam->update([
                'name' => mb_trim($tagTeamData->name),
                'signature_move' => $tagTeamData->signature_move,
            ]);

            $updateDate = now();

            // Handle partnership changes through membership service using membership data
            $membershipData = $tagTeamData->getMembershipData();

            $this->membershipService->updateMembership(
                $lockedTagTeam,
                $membershipData,
                $updateDate,
            );

            if ($tagTeamData->employment_date) {
                if (! $lockedTagTeam->isEmployed()) {
                    $this->employAction->handle($lockedTagTeam, $tagTeamData->employment_date);
                } else {
                    $this->employCurrentWrestlersAction->handle($lockedTagTeam, $tagTeamData->employment_date);
                    $this->employCurrentManagersAction->handle($lockedTagTeam, $tagTeamData->employment_date);
                }
            }

            return $lockedTagTeam;
        });
    }
}
