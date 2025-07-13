<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Concerns\ManagesDates;
use App\Actions\Managers\RetireAction as ManagersRetireAction;
use App\Actions\Managers\UnretireAction as ManagersUnretireAction;
use App\Actions\TagTeams\RetireAction as TagTeamsRetireAction;
use App\Actions\TagTeams\UnretireAction as TagTeamsUnretireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Actions\Wrestlers\UnretireAction as WrestlersUnretireAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Base class for all stable actions.
 *
 * Provides common functionality for actions that operate on stables:
 * - Centralized repository access through dependency injection
 * - Status transition management capabilities for debut, retirement, etc.
 * - Foundation for stable-related business operations (merge, split, disband)
 * - Standardized repository access patterns
 * - Multi-member management for wrestlers, tag teams, and managers
 *
 * STABLE LIFECYCLE STATES:
 * 1. Created (inactive) - Stable exists but not available for storylines
 * 2. Debuted - Available for storylines and championship opportunities
 * 3. Disbanded - Temporarily disbanded but can reunite
 * 4. Retired - Permanently retired from competition
 * 5. Deleted - Soft deleted, can be restored
 *
 * MEMBER MANAGEMENT:
 * - Can have 0 or more wrestlers
 * - Can have 0 or more tag teams
 * - Can have 0 or more managers
 * - All status actions cascade to available members
 * - Member changes require proper date tracking for memberships
 *
 * Note: Stables cannot be employed, suspended, or injured (only debut/disband/retire)
 */
abstract class BaseStableAction
{
    use ManagesDates;

    /**
     * Create a new base stable action instance.
     */
    public function __construct(
        protected StableRepository $stableRepository
    ) {}

    /**
     * Employ pre-filtered members (wrestlers, tag teams, and managers) as part of stable debut.
     *
     * Note: Only Titles can be "debuted". For wrestlers, tag teams, and managers,
     * we employ them as part of the stable's establishment process.
     *
     * @param  Collection<int, Wrestler>  $wrestlers  Pre-filtered wrestlers to employ
     * @param  Collection<int, TagTeam>  $tagTeams  Pre-filtered tag teams to employ
     * @param  Collection<int, Manager>  $managers  Pre-filtered managers to employ
     * @param  Carbon  $effectiveDate  The employment date for the stable debut
     */
    protected function employMembersForDebut(
        $wrestlers,
        $tagTeams,
        $managers,
        Carbon $effectiveDate
    ): void {
        // Note: Since only Titles can be debuted, we employ members instead
        // This method would be implemented by specific stable actions that need
        // to employ members as part of the stable debut process

        // This is a placeholder method that child classes can override
        // if they need to employ members during stable debut
    }

    /**
     * Retire pre-filtered members (wrestlers, tag teams, and managers).
     *
     * @param  Collection<int, Wrestler>  $wrestlers  Pre-filtered wrestlers to retire
     * @param  Collection<int, TagTeam>  $tagTeams  Pre-filtered tag teams to retire
     * @param  Collection<int, Manager>  $managers  Pre-filtered managers to retire
     * @param  Carbon  $retirementDate  The retirement date
     * @param  WrestlersRetireAction  $wrestlersRetireAction  Action to retire wrestlers
     * @param  TagTeamsRetireAction  $tagTeamsRetireAction  Action to retire tag teams
     * @param  ManagersRetireAction  $managersRetireAction  Action to retire managers
     */
    protected function retireMembers(
        $wrestlers,
        $tagTeams,
        $managers,
        Carbon $retirementDate,
        $wrestlersRetireAction,
        $tagTeamsRetireAction,
        $managersRetireAction
    ): void {
        // Retire the provided wrestlers
        $wrestlers->each(fn ($wrestler) => $wrestlersRetireAction->handle($wrestler, $retirementDate));

        // Retire the provided tag teams
        $tagTeams->each(fn ($tagTeam) => $tagTeamsRetireAction->handle($tagTeam, $retirementDate));

        // Retire the provided managers
        $managers->each(fn ($manager) => $managersRetireAction->handle($manager, $retirementDate));
    }

    /**
     * Unretire pre-filtered members (wrestlers, tag teams, and managers).
     *
     * @param  Collection<int, Wrestler>  $wrestlers  Pre-filtered wrestlers to unretire
     * @param  Collection<int, TagTeam>  $tagTeams  Pre-filtered tag teams to unretire
     * @param  Collection<int, Manager>  $managers  Pre-filtered managers to unretire
     * @param  Carbon  $unretirementDate  The unretirement date
     * @param  WrestlersUnretireAction  $wrestlersUnretireAction  Action to unretire wrestlers
     * @param  TagTeamsUnretireAction  $tagTeamsUnretireAction  Action to unretire tag teams
     * @param  ManagersUnretireAction  $managersUnretireAction  Action to unretire managers
     */
    protected function unretireMembers(
        $wrestlers,
        $tagTeams,
        $managers,
        Carbon $unretirementDate,
        $wrestlersUnretireAction,
        $tagTeamsUnretireAction,
        $managersUnretireAction
    ): void {
        // Unretire the provided wrestlers
        $wrestlers->each(fn ($wrestler) => $wrestlersUnretireAction->handle($wrestler, $unretirementDate));

        // Unretire the provided tag teams
        $tagTeams->each(fn ($tagTeam) => $tagTeamsUnretireAction->handle($tagTeam, $unretirementDate));

        // Unretire the provided managers
        $managers->each(fn ($manager) => $managersUnretireAction->handle($manager, $unretirementDate));
    }
}
