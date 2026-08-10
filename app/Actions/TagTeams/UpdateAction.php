<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Data\TagTeams\TagTeamData;
use App\Models\TagTeams\TagTeam;
use App\Services\TagTeamMembershipService;
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
            // Update the tag team's basic information
            $tagTeam->update([
                'name' => mb_trim($tagTeamData->name),
                'signature_move' => $tagTeamData->signature_move,
            ]);

            $updateDate = now();

            // Handle partnership changes through membership service using membership data
            $membershipData = $tagTeamData->getMembershipData();

            $this->membershipService->updateMembership(
                $tagTeam,
                $membershipData,
                $updateDate,
            );

            if ($tagTeamData->employment_date) {
                if (! $tagTeam->isEmployed()) {
                    $this->employAction->handle($tagTeam, $tagTeamData->employment_date);
                } else {
                    $this->employCurrentWrestlersAction->handle($tagTeam, $tagTeamData->employment_date);
                    $this->employCurrentManagersAction->handle($tagTeam, $tagTeamData->employment_date);
                }
            }

            return $tagTeam;
        });
    }
}
