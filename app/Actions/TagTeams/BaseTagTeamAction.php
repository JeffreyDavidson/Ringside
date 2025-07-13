<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\ManagesDates;
use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Actions\Managers\ReinstateAction as ManagersReinstateAction;
use App\Actions\Managers\RetireAction as ManagersRetireAction;
use App\Actions\Managers\SuspendAction as ManagersSuspendAction;
use App\Actions\Managers\UnretireAction as ManagersUnretireAction;
use App\Actions\Wrestlers\EmployAction as WrestlersEmployAction;
use App\Actions\Wrestlers\ReinstateAction as WrestlersReinstateAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Actions\Wrestlers\SuspendAction as WrestlersSuspendAction;
use App\Actions\Wrestlers\UnretireAction as WrestlersUnretireAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Base class for all tag team actions.
 *
 * Provides common functionality for actions that operate on tag teams:
 * - Centralized repository access through dependency injection
 * - Status transition management capabilities for employment, retirement, etc.
 * - Foundation for tag team-related business operations
 * - Standardized repository access patterns
 * - Multi-member management for wrestlers and managers
 *
 * TAG TEAM LIFECYCLE STATES:
 * 1. Created (unemployed) - Tag team exists but not available for competition
 * 2. Employed - Available for matches and championship opportunities
 * 3. Suspended - Employed but temporarily restricted from competition
 * 4. Released - Employment ended, no longer with promotion
 * 5. Retired - Partnership ended, permanently unavailable for competition
 * 6. Deleted - Soft deleted, can be restored
 *
 * MEMBER MANAGEMENT:
 * - Must have exactly 2 wrestlers at all times (wrestlerA and wrestlerB)
 * - Can have 0 or more managers
 * - All employment actions cascade to available members
 * - Member changes require proper date tracking for partnerships
 *
 * Note: Tag teams cannot be injured (only individual wrestlers can be)
 */
abstract class BaseTagTeamAction
{
    use ManagesDates;

    /**
     * Create a new base tag team action instance.
     */
    public function __construct(
        protected TagTeamRepository $tagTeamRepository
    ) {}

    /**
     * Employ pre-filtered members (wrestlers and managers).
     *
     * @param  Collection<int, Wrestler>  $wrestlers  Pre-filtered wrestlers to employ
     * @param  Collection<int, Manager>  $managers  Pre-filtered managers to employ
     * @param  Carbon  $employmentDate  The employment date
     * @param  WrestlersEmployAction  $wrestlersEmployAction  Action to employ wrestlers
     * @param  ManagersEmployAction  $managersEmployAction  Action to employ managers
     */
    protected function employMembers(
        $wrestlers,
        $managers,
        Carbon $employmentDate,
        $wrestlersEmployAction,
        $managersEmployAction
    ): void {
        // Employ the provided wrestlers
        $wrestlers->each(fn ($wrestler) => $wrestlersEmployAction->handle($wrestler, $employmentDate));

        // Employ the provided managers
        $managers->each(fn ($manager) => $managersEmployAction->handle($manager, $employmentDate));
    }

    /**
     * Reinstate pre-filtered members (wrestlers and managers).
     *
     * @param  Collection<int, Wrestler>  $wrestlers  Pre-filtered wrestlers to reinstate
     * @param  Collection<int, Manager>  $managers  Pre-filtered managers to reinstate
     * @param  Carbon  $reinstatementDate  The reinstatement date
     * @param  WrestlersReinstateAction  $wrestlersReinstateAction  Action to reinstate wrestlers
     * @param  ManagersReinstateAction  $managersReinstateAction  Action to reinstate managers
     */
    protected function reinstateMembers(
        $wrestlers,
        $managers,
        Carbon $reinstatementDate,
        $wrestlersReinstateAction,
        $managersReinstateAction
    ): void {
        // Reinstate the provided wrestlers
        $wrestlers->each(fn ($wrestler) => $wrestlersReinstateAction->handle($wrestler, $reinstatementDate));

        // Reinstate the provided managers
        $managers->each(fn ($manager) => $managersReinstateAction->handle($manager, $reinstatementDate));
    }

    /**
     * Suspend pre-filtered members (wrestlers and managers).
     *
     * @param  Collection<int, Wrestler>  $wrestlers  Pre-filtered wrestlers to suspend
     * @param  Collection<int, Manager>  $managers  Pre-filtered managers to suspend
     * @param  Carbon  $suspensionDate  The suspension date
     * @param  WrestlersSuspendAction  $wrestlersSuspendAction  Action to suspend wrestlers
     * @param  ManagersSuspendAction  $managersSuspendAction  Action to suspend managers
     */
    protected function suspendMembers(
        $wrestlers,
        $managers,
        Carbon $suspensionDate,
        $wrestlersSuspendAction,
        $managersSuspendAction
    ): void {
        // Suspend the provided wrestlers
        $wrestlers->each(fn ($wrestler) => $wrestlersSuspendAction->handle($wrestler, $suspensionDate));

        // Suspend the provided managers
        $managers->each(fn ($manager) => $managersSuspendAction->handle($manager, $suspensionDate));
    }

    /**
     * Unretire pre-filtered members (wrestlers and managers).
     *
     * @param  Collection<int, Wrestler>  $wrestlers  Pre-filtered wrestlers to unretire
     * @param  Collection<int, Manager>  $managers  Pre-filtered managers to unretire
     * @param  Carbon  $unretirementDate  The unretirement date
     * @param  WrestlersUnretireAction  $wrestlersUnretireAction  Action to unretire wrestlers
     * @param  ManagersUnretireAction  $managersUnretireAction  Action to unretire managers
     */
    protected function unretireMembers(
        $wrestlers,
        $managers,
        Carbon $unretirementDate,
        $wrestlersUnretireAction,
        $managersUnretireAction
    ): void {
        // Unretire the provided wrestlers
        $wrestlers->each(fn ($wrestler) => $wrestlersUnretireAction->handle($wrestler, $unretirementDate));

        // Unretire the provided managers
        $managers->each(fn ($manager) => $managersUnretireAction->handle($manager, $unretirementDate));
    }

    /**
     * Restore former members of a tag team after restoration.
     *
     * This method handles the complex logic of reuniting former tag team members
     * while respecting current partnerships and avoiding conflicts.
     *
     * @param  TagTeam  $tagTeam  The restored tag team
     * @param  Carbon  $restorationDate  The restoration date
     * @param  bool  $forceReunite  Whether to force wrestlers out of current teams
     * @param  TagTeamRepository  $tagTeamRepository  Repository for tag team operations
     */
    protected function restoreFormerMembers(
        TagTeam $tagTeam,
        Carbon $restorationDate,
        bool $forceReunite,
        TagTeamRepository $tagTeamRepository
    ): void {
        // Get the last known wrestlers before deletion
        $formerWrestlers = $tagTeam->wrestlers()
            ->withTrashed()
            ->wherePivotNull('left_at')
            ->get();

        // Get the last known managers before deletion
        $formerManagers = $tagTeam->managers()
            ->withTrashed()
            ->wherePivotNull('fired_at')
            ->get();

        if ($forceReunite) {
            // Force reunion: remove wrestlers from current teams and restore partnerships
            $this->forceReuniteMembers($tagTeam, $formerWrestlers, $formerManagers, $restorationDate, $tagTeamRepository);
        } else {
            // Conservative approach: only restore available members
            $this->restoreAvailableMembers($tagTeam, $formerWrestlers, $formerManagers, $restorationDate, $tagTeamRepository);
        }
    }

    /**
     * Force reunion of former members by removing them from current teams.
     *
     * @param  Collection<int, Wrestler>  $formerWrestlers
     * @param  Collection<int, Manager>  $formerManagers
     */
    private function forceReuniteMembers(
        TagTeam $tagTeam,
        Collection $formerWrestlers,
        Collection $formerManagers,
        Carbon $restorationDate,
        TagTeamRepository $tagTeamRepository
    ): void {
        // Remove wrestlers from any current tag teams
        $formerWrestlers->each(function ($wrestler) use ($restorationDate) {
            $currentTagTeams = $wrestler->currentTagTeams; // @phpstan-ignore-line property.notFound
            $currentTagTeams->each(function ($currentTeam) use ($wrestler, $restorationDate) {
                $this->tagTeamRepository->removeWrestler($currentTeam, $wrestler, $restorationDate);
            });
        });

        // Add wrestlers back to this tag team
        $tagTeamRepository->addWrestlers($tagTeam, $formerWrestlers, $restorationDate);

        // Restore manager relationships (managers can manage multiple teams)
        $tagTeamRepository->addManagers($tagTeam, $formerManagers, $restorationDate);
    }

    /**
     * Restore only available members (not currently in other teams).
     *
     * @param  Collection<int, Wrestler>  $formerWrestlers
     * @param  Collection<int, Manager>  $formerManagers
     */
    private function restoreAvailableMembers(
        TagTeam $tagTeam,
        Collection $formerWrestlers,
        Collection $formerManagers,
        Carbon $restorationDate,
        TagTeamRepository $tagTeamRepository
    ): void {
        // Only restore wrestlers who are not currently in other tag teams
        $availableWrestlers = $formerWrestlers->filter(function ($wrestler) {
            return $wrestler->currentTagTeams->isEmpty(); // @phpstan-ignore-line property.notFound
        });

        // Add available wrestlers back to the tag team
        if ($availableWrestlers->isNotEmpty()) {
            $tagTeamRepository->addWrestlers($tagTeam, $availableWrestlers, $restorationDate);
        }

        // Restore manager relationships (managers can manage multiple teams)
        $tagTeamRepository->addManagers($tagTeam, $formerManagers, $restorationDate);
    }

    /**
     * Retire pre-filtered members (wrestlers and managers).
     *
     * @param  Collection<int, Wrestler>  $wrestlers  Pre-filtered wrestlers to retire
     * @param  Collection<int, Manager>  $managers  Pre-filtered managers to retire
     * @param  Carbon  $retirementDate  The retirement date
     * @param  WrestlersRetireAction  $wrestlersRetireAction  Action to retire wrestlers
     * @param  ManagersRetireAction  $managersRetireAction  Action to retire managers
     */
    protected function retireMembers(
        $wrestlers,
        $managers,
        Carbon $retirementDate,
        $wrestlersRetireAction,
        $managersRetireAction
    ): void {
        // Retire the provided wrestlers
        $wrestlers->each(fn ($wrestler) => $wrestlersRetireAction->handle($wrestler, $retirementDate));

        // Retire the provided managers
        $managers->each(fn ($manager) => $managersRetireAction->handle($manager, $retirementDate));
    }
}
