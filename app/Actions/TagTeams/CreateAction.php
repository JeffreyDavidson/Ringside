<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Data\TagTeams\TagTeamData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Services\TagTeamMembershipService;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Create a new create action instance.
     */
    public function __construct(
        protected TagTeamMembershipService $membershipService,
        protected EmployAction $employAction,
    ) {}

    /**
     * Create a tag team and establish its initial relationships.
     */
    public function handle(TagTeamData $tagTeamData): TagTeam
    {
        return DB::transaction(function () use ($tagTeamData): TagTeam {
            // Create the base tag team record
            $tagTeam = TagTeam::query()->create([
                'name' => mb_trim($tagTeamData->name),
                'signature_move' => $tagTeamData->signature_move,
            ]);

            // Get membership data
            $membershipData = $tagTeamData->getMembershipData();

            $this->membershipService->establishMembership(
                $tagTeam,
                $membershipData,
                $tagTeamData->getJoinDate(),
            );

            if ($tagTeamData->employment_date) {
                $this->employAction->handle($tagTeam, $tagTeamData->employment_date);
            }

            return $tagTeam;
        });
    }
}
