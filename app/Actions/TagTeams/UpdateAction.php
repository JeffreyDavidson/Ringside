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
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction
{
    use AsAction;

    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected TagTeamValidationService $validationService,
        protected TagTeamMembershipService $membershipService
    ) {}

    /**
     * Update a tag team with comprehensive business rule validation and service integration.
     *
     * This handles the complete tag team update workflow using dedicated services:
     * - Validates all business rules for updates including uniqueness and availability
     * - Updates tag team information with validated data
     * - Manages partnership changes through membership service
     * - Manages manager relationship changes through membership service
     * - Handles employment workflows through lifecycle service
     * - Maintains data integrity and business rule compliance throughout
     *
     * @param  TagTeam  $tagTeam  The tag team to update
     * @param  TagTeamData  $tagTeamData  The updated tag team information
     * @return TagTeam The updated tag team instance with all changes applied
     *
     * @example
     * ```php
     * // Update tag team name only
     * $tagTeamData = new TagTeamData([
     *     'name' => 'The New Day (Updated)',
     *     'wrestlerA' => $existingWrestlerA,
     *     'wrestlerB' => $existingWrestlerB
     * ]);
     * $updatedTeam = UpdateAction::run($tagTeam, $tagTeamData);
     *
     * // Change partners and employ unemployed tag team
     * $tagTeamData = new TagTeamData([
     *     'name' => 'The New Day',
     *     'wrestlerA' => $kofi,
     *     'wrestlerB' => $bigE,
     *     'employment_date' => Carbon::parse('2024-01-01')
     * ]);
     * $updatedTeam = UpdateAction::run($unemployedTeam, $tagTeamData);
     * ```
     */
    public function handle(TagTeam $tagTeam, TagTeamData $tagTeamData): TagTeam
    {
        // Validate all business rules for update
        $this->validationService->validateForUpdate($tagTeam, $tagTeamData);

        return DB::transaction(function () use ($tagTeam, $tagTeamData): TagTeam {
            // Update the tag team's basic information
            $tagTeam->update([
                'name' => mb_trim($tagTeamData->name),
                'signature_move' => $tagTeamData->signature_move,
            ]);

            $updateDate = now();

            // Handle partnership changes through membership service
            $wrestlers = collect([$tagTeamData->wrestlerA, $tagTeamData->wrestlerB])->filter();
            $managers = $tagTeamData->managers ?? collect();

            $newWrestlers = $this->membershipService->updatePartnerships(
                $tagTeam,
                $wrestlers,
                $updateDate,
                false // Don't employ through membership service - handle separately if needed
            );

            $newManagers = $this->membershipService->updateManagerRelationships(
                $tagTeam,
                $managers,
                $updateDate,
                false // Don't employ through membership service - handle separately if needed
            );

            // Handle employment for newly added members if employment date provided
            if ($tagTeamData->employment_date) {
                // Employ new members first
                if ($newWrestlers->isNotEmpty() || $newManagers->isNotEmpty()) {
                    $allNewMembers = $newWrestlers->merge($newManagers);
                    $this->membershipService->employMembers($allNewMembers, $tagTeamData->employment_date);
                }

                // Handle tag team employment if not already employed
                if (! $tagTeam->isEmployed()) {
                    StatusTransitionPipeline::employ($tagTeam, $tagTeamData->employment_date)
                        ->withCascade(EmploymentCascadeStrategy::wrestlers())
                        ->withCascade(EmploymentCascadeStrategy::managers())
                        ->execute();
                }
            }

            return $tagTeam;
        });
    }
}
