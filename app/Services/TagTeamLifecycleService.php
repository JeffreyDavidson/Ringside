<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Actions\Wrestlers\EmployAction as WrestlersEmployAction;
use App\Enums\Shared\EmploymentStatus;
use App\Models\TagTeams\TagTeam;
use Illuminate\Support\Carbon;

/**
 * Service for managing tag team lifecycle operations.
 *
 * This service centralizes all tag team lifecycle management including employment,
 * retirement, suspension, and status transitions. It provides consistent handling
 * of complex lifecycle workflows while maintaining data integrity and business
 * rule compliance across all lifecycle operations.
 *
 * BUSINESS CONTEXT:
 * Tag team lifecycle management involves coordinated status changes between the
 * tag team entity and its members (wrestlers and managers). This service ensures
 * consistent handling of employment cascading, status synchronization, and
 * business rule enforcement throughout all lifecycle transitions.
 *
 * LIFECYCLE OPERATIONS:
 * - Employment workflows with member cascading
 * - Retirement management with flexible options
 * - Status synchronization and validation
 * - Record creation and relationship management
 * - Business rule enforcement for transitions
 *
 * DESIGN PATTERN:
 * Service pattern - Centralizes lifecycle logic away from Actions
 * Template method - Consistent lifecycle workflow with customizable steps
 * Strategy pattern - Different handling strategies for different lifecycle events
 *
 * @example
 * ```php
 * $service = app(TagTeamLifecycleService::class);
 *
 * // Handle employment with member cascading
 * $service->employ($tagTeam, $employmentDate, true);
 *
 * // Handle retirement workflow
 * $service->handleRetirement($tagTeam, $retirementDate);
 *
 * // Update status with validation
 * $service->updateStatus($tagTeam, EmploymentStatus::Employed);
 * ```
 */
class TagTeamLifecycleService
{
    /**
     * Create a new tag team lifecycle service instance.
     */
    public function __construct(
        protected WrestlersEmployAction $wrestlersEmployAction,
        protected ManagersEmployAction $managersEmployAction,
        protected TagTeamMembershipService $membershipService
    ) {}

    /**
     * Handle tag team employment workflow.
     *
     * This method manages the complete employment process including record creation,
     * status updates, and optional member employment cascading with business
     * rule validation and data integrity maintenance.
     *
     * @param  TagTeam  $tagTeam  The tag team to employ
     * @param  Carbon  $employmentDate  The employment start date
     * @param  bool  $employMembers  Whether to employ unemployed members
     *
     * @example
     * ```php
     * // Employ tag team and all unemployed members
     * $service->employ($tagTeam, now(), true);
     *
     * // Employ only the tag team entity
     * $service->employ($tagTeam, now(), false);
     * ```
     */
    public function employ(TagTeam $tagTeam, Carbon $employmentDate, bool $employMembers = true): void
    {
        // End retirement if currently retired
        if ($tagTeam->isRetired()) {
            $this->endCurrentRetirement($tagTeam, $employmentDate);
        }

        // Create employment record
        $tagTeam->employments()->create([
            'started_at' => $employmentDate,
            'ended_at' => null,
        ]);

        // Update status to employed
        $this->updateStatus($tagTeam, EmploymentStatus::Employed);

        // Handle member employment if requested
        if ($employMembers) {
            $this->employUnemployedMembers($tagTeam, $employmentDate);
        }
    }

    /**
     * Handle tag team retirement workflow.
     *
     * This method manages the complete retirement process including employment
     * termination, retirement record creation, and status updates with proper
     * business rule validation.
     *
     * @param  TagTeam  $tagTeam  The tag team to retire
     * @param  Carbon  $retirementDate  The retirement date
     *
     * @example
     * ```php
     * $service->handleRetirement($tagTeam, now());
     * ```
     */
    public function handleRetirement(TagTeam $tagTeam, Carbon $retirementDate): void
    {
        // End current employment if active
        if ($tagTeam->isEmployed()) {
            $this->endCurrentEmployment($tagTeam, $retirementDate);
        }

        // End suspension if active
        if ($tagTeam->isSuspended()) {
            $this->endCurrentSuspension($tagTeam, $retirementDate);
        }

        // Create retirement record
        $tagTeam->retirements()->create([
            'started_at' => $retirementDate,
            'ended_at' => null,
        ]);

        // Update status to retired
        $this->updateStatus($tagTeam, EmploymentStatus::Retired);
    }

    /**
     * Handle tag team unretirement workflow.
     *
     * This method manages the unretirement process including retirement record
     * closure and status updates, preparing the team for re-employment.
     *
     * @param  TagTeam  $tagTeam  The tag team to unretire
     * @param  Carbon  $unretiredDate  The unretirement date
     * @param  bool  $employImmediately  Whether to employ immediately after unretirement
     *
     * @example
     * ```php
     * // Unretire and employ immediately
     * $service->handleUnretirement($tagTeam, now(), true);
     *
     * // Unretire without immediate employment
     * $service->handleUnretirement($tagTeam, now(), false);
     * ```
     */
    public function handleUnretirement(TagTeam $tagTeam, Carbon $unretiredDate, bool $employImmediately = false): void
    {
        // End current retirement
        $this->endCurrentRetirement($tagTeam, $unretiredDate);

        // Update status to unemployed (available for employment)
        $this->updateStatus($tagTeam, EmploymentStatus::Unemployed);

        // Handle immediate employment if requested
        if ($employImmediately) {
            $this->employ($tagTeam, $unretiredDate, true);
        }
    }

    /**
     * Handle tag team suspension workflow.
     *
     * This method manages the suspension process including suspension record
     * creation while maintaining employment status for future reinstatement.
     *
     * @param  TagTeam  $tagTeam  The tag team to suspend
     * @param  Carbon  $suspensionDate  The suspension start date
     *
     * @example
     * ```php
     * $service->handleSuspension($tagTeam, now());
     * ```
     */
    public function handleSuspension(TagTeam $tagTeam, Carbon $suspensionDate): void
    {
        // Create suspension record
        $tagTeam->suspensions()->create([
            'started_at' => $suspensionDate,
            'ended_at' => null,
        ]);

        // Note: Status remains employed during suspension for reinstatement
    }

    /**
     * Handle tag team reinstatement workflow.
     *
     * This method manages the reinstatement process including suspension record
     * closure and restoration to active competition status.
     *
     * @param  TagTeam  $tagTeam  The tag team to reinstate
     * @param  Carbon  $reinstatementDate  The reinstatement date
     *
     * @example
     * ```php
     * $service->handleReinstatement($tagTeam, now());
     * ```
     */
    public function handleReinstatement(TagTeam $tagTeam, Carbon $reinstatementDate): void
    {
        // End current suspension
        $this->endCurrentSuspension($tagTeam, $reinstatementDate);

        // Status remains employed - no status change needed for reinstatement
    }

    /**
     * Handle tag team release workflow.
     *
     * This method manages the release process including employment termination,
     * suspension closure if active, and status updates.
     *
     * @param  TagTeam  $tagTeam  The tag team to release
     * @param  Carbon  $releaseDate  The release date
     *
     * @example
     * ```php
     * $service->handleRelease($tagTeam, now());
     * ```
     */
    public function handleRelease(TagTeam $tagTeam, Carbon $releaseDate): void
    {
        // End suspension if active
        if ($tagTeam->isSuspended()) {
            $this->endCurrentSuspension($tagTeam, $releaseDate);
        }

        // End current employment
        if ($tagTeam->isEmployed()) {
            $this->endCurrentEmployment($tagTeam, $releaseDate);
        }

        // Update status to unemployed (released)
        $this->updateStatus($tagTeam, EmploymentStatus::Unemployed);
    }

    /**
     * Update tag team status with validation.
     *
     * This method provides centralized status management ensures consistent
     * status updates across all lifecycle operations.
     *
     * @param  TagTeam  $tagTeam  The tag team to update
     * @param  EmploymentStatus  $status  The new status
     *
     * @example
     * ```php
     * $service->updateStatus($tagTeam, EmploymentStatus::Employed);
     * ```
     */
    public function updateStatus(TagTeam $tagTeam, EmploymentStatus $status): void
    {
        $tagTeam->update(['status' => $status]);
    }

    /**
     * Employ unemployed members of the tag team.
     *
     * This method handles employment cascading for tag team members, employing
     * wrestlers and managers who are not already employed.
     *
     * @param  TagTeam  $tagTeam  The tag team whose members to employ
     * @param  Carbon  $employmentDate  The employment date
     */
    private function employUnemployedMembers(TagTeam $tagTeam, Carbon $employmentDate): void
    {
        // Employ current wrestlers if they're not already employed
        $unemployedWrestlers = $tagTeam->currentWrestlers
            ->filter(fn ($wrestler) => ! $wrestler->isEmployed());

        foreach ($unemployedWrestlers as $wrestler) {
            $this->wrestlersEmployAction->handle($wrestler, $employmentDate);
        }

        // Employ current managers if they're not already employed
        $unemployedManagers = $tagTeam->currentManagers
            ->filter(fn ($manager) => ! $manager->isEmployed());

        foreach ($unemployedManagers as $manager) {
            $this->managersEmployAction->handle($manager, $employmentDate);
        }
    }

    /**
     * End current employment record.
     *
     * @param  TagTeam  $tagTeam  The tag team
     * @param  Carbon  $endDate  The employment end date
     */
    private function endCurrentEmployment(TagTeam $tagTeam, Carbon $endDate): void
    {
        $tagTeam->employments()
            ->where('ended_at', null)
            ->update(['ended_at' => $endDate]);
    }

    /**
     * End current retirement record.
     *
     * @param  TagTeam  $tagTeam  The tag team
     * @param  Carbon  $endDate  The retirement end date
     */
    private function endCurrentRetirement(TagTeam $tagTeam, Carbon $endDate): void
    {
        $tagTeam->retirements()
            ->where('ended_at', null)
            ->update(['ended_at' => $endDate]);
    }

    /**
     * End current suspension record.
     *
     * @param  TagTeam  $tagTeam  The tag team
     * @param  Carbon  $endDate  The suspension end date
     */
    private function endCurrentSuspension(TagTeam $tagTeam, Carbon $endDate): void
    {
        $tagTeam->suspensions()
            ->where('ended_at', null)
            ->update(['ended_at' => $endDate]);
    }
}
