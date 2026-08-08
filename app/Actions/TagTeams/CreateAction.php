<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\EmploymentCascadeStrategy;
use App\Actions\Concerns\StatusTransitionPipeline;
use App\Data\TagTeams\TagTeamData;
use App\Models\TagTeams\TagTeam;
use App\Services\TagTeamMembershipService;
use App\Services\TagTeamValidationService;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Create a new create action instance.
     */
    public function __construct(
        protected TagTeamValidationService $validationService,
        protected TagTeamMembershipService $membershipService
    ) {}

    /**
     * Create a tag team with comprehensive business rule validation and service integration.
     *
     * This handles the complete tag team creation workflow using dedicated services:
     * - Validates all business rules and data integrity constraints
     * - Creates the tag team record with validated information
     * - Adds founding partners and managers through membership service
     * - Handles employment workflows through StatusTransitionPipeline
     * - Ensures consistent data integrity and business rule compliance
     *
     * @param  TagTeamData  $tagTeamData  The data transfer object containing tag team information
     * @return TagTeam The newly created tag team with all members and relationships
     */
    public function handle(TagTeamData $tagTeamData): TagTeam
    {
        // Validate all business rules for creation
        $this->validationService->validateForCreation($tagTeamData);

        return DB::transaction(function () use ($tagTeamData): TagTeam {
            // Create the base tag team record
            $tagTeam = TagTeam::query()->create([
                'name' => mb_trim($tagTeamData->name),
                'signature_move' => $tagTeamData->signature_move,
            ]);

            // Get membership data
            $membershipData = $tagTeamData->getMembershipData();

            // Add founding members through membership service
            $this->membershipService->addFoundingMembers(
                $tagTeam,
                $membershipData->getWrestlers(),
                $membershipData->getManagers(),
                $tagTeamData->getJoinDate(),
                false // Don't employ through membership service - handle separately if needed
            );

            // Handle employment through StatusTransitionPipeline if requested
            if ($tagTeamData->employment_date) {
                StatusTransitionPipeline::employ($tagTeam, $tagTeamData->employment_date)
                    ->withCascade(EmploymentCascadeStrategy::wrestlers())
                    ->withCascade(EmploymentCascadeStrategy::managers())
                    ->execute();
            }

            return $tagTeam;
        });
    }
}
