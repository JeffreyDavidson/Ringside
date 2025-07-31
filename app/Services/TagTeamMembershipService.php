<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Actions\Wrestlers\EmployAction as WrestlersEmployAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Service for managing tag team membership operations.
 *
 * This service centralizes all tag team membership-related operations including
 * partnership management, manager relationships, and member employment handling.
 * It provides a consistent interface for managing complex membership transitions
 * while maintaining data integrity and business rule compliance.
 *
 * BUSINESS CONTEXT:
 * Tag team membership involves complex relationships between wrestlers, managers,
 * and the tag team entity itself. This service ensures consistent handling of:
 * - Partnership additions and removals with proper date tracking
 * - Manager relationship management with hiring/firing workflows
 * - Employment cascading for new and existing members
 * - Business rule validation for membership changes
 *
 * DESIGN PATTERN:
 * Service pattern - Centralizes complex business logic away from Actions
 * Repository pattern - Provides clean interface for membership operations
 * Strategy pattern - Different handling for different membership types
 *
 * @example
 * ```php
 * $service = app(TagTeamMembershipService::class);
 *
 * // Add founding members to new tag team
 * $service->addFoundingMembers($tagTeam, $wrestlers, $managers, $joinDate);
 *
 * // Update existing partnerships
 * $newPartners = $service->updatePartnerships($tagTeam, $newWrestlers, $changeDate);
 *
 * // Handle employment for members
 * $service->employMembers($members, $employmentDate);
 * ```
 */
class TagTeamMembershipService
{
    /**
     * Create a new tag team membership service instance.
     */
    public function __construct(
        protected WrestlersEmployAction $wrestlersEmployAction,
        protected ManagersEmployAction $managersEmployAction
    ) {}

    /**
     * Add founding members to a newly created tag team.
     *
     * This method handles the initial membership setup for new tag teams with
     * proper validation, date tracking, and optional employment handling.
     *
     * @param  TagTeam  $tagTeam  The tag team to add founding members to
     * @param  Collection<int, Wrestler>  $wrestlers  Collection of founding wrestler partners
     * @param  Collection<int, Manager>|null  $managers  Collection of founding managers
     * @param  Carbon  $joinDate  The founding date for all members
     * @param  bool  $employIfNeeded  Whether to employ unemployed members
     *
     * @example
     * ```php
     * $wrestlers = collect([$matt, $jeff]);
     * $managers = collect([$lita]);
     * $service->addFoundingMembers($tagTeam, $wrestlers, $managers, now(), true);
     * ```
     */
    public function addFoundingMembers(
        TagTeam $tagTeam,
        Collection $wrestlers,
        ?Collection $managers,
        Carbon $joinDate,
        bool $employIfNeeded = false
    ): void {
        // Validate founding members
        $this->validateFoundingMembers($wrestlers, $managers);

        // Add wrestlers as founding partners
        $this->attachWrestlersToTeam($tagTeam, $wrestlers, $joinDate);

        // Add managers if provided
        if ($managers && $managers->isNotEmpty()) {
            $this->attachManagersToTeam($tagTeam, $managers, $joinDate);
        }

        // Handle employment if requested
        if ($employIfNeeded) {
            $this->employMembers($wrestlers, $joinDate);
            if ($managers && $managers->isNotEmpty()) {
                $this->employMembers($managers, $joinDate);
            }
        }
    }

    /**
     * Update wrestler partnerships for an existing tag team.
     *
     * This method handles partnership transitions by comparing current and desired
     * partner configurations, ending old partnerships and creating new ones.
     *
     * @param  TagTeam  $tagTeam  The tag team to update partnerships for
     * @param  Collection<int, Wrestler>  $newWrestlers  Desired new partner configuration
     * @param  Carbon  $changeDate  The date of partnership changes
     * @param  bool  $employIfNeeded  Whether to employ newly added partners
     * @return Collection<int, Wrestler> Collection of newly added partners
     *
     * @example
     * ```php
     * $newPartners = collect([$kofi, $bigE]);
     * $addedPartners = $service->updatePartnerships($tagTeam, $newPartners, now(), true);
     * ```
     */
    public function updatePartnerships(
        TagTeam $tagTeam,
        Collection $newWrestlers,
        Carbon $changeDate,
        bool $employIfNeeded = false
    ): Collection {
        // Validate new wrestler collection
        $newWrestlers->ensure(Wrestler::class);

        // Get current partnerships
        $currentWrestlers = $tagTeam->currentWrestlers;

        // Identify partnerships to end and add
        $partnersToRemove = $currentWrestlers->diff($newWrestlers);
        $partnersToAdd = $newWrestlers->diff($currentWrestlers);

        // Validate new partners before making changes
        if ($partnersToAdd->isNotEmpty()) {
            $this->validateNewPartners($tagTeam, $partnersToAdd);
        }

        // End old partnerships
        if ($partnersToRemove->isNotEmpty()) {
            $this->detachWrestlersFromTeam($tagTeam, $partnersToRemove, $changeDate);
        }

        // Add new partnerships
        if ($partnersToAdd->isNotEmpty()) {
            $this->attachWrestlersToTeam($tagTeam, $partnersToAdd, $changeDate);

            // Handle employment for new partners
            if ($employIfNeeded) {
                $this->employMembers($partnersToAdd, $changeDate);
            }
        }

        return $partnersToAdd;
    }

    /**
     * Update manager relationships for an existing tag team.
     *
     * This method handles manager relationship transitions by comparing current
     * and desired manager configurations, ending old relationships and creating new ones.
     *
     * @param  TagTeam  $tagTeam  The tag team to update manager relationships for
     * @param  Collection<int, Manager>|array<int, Manager>  $newManagers  Desired new managers
     * @param  Carbon  $changeDate  The date of relationship changes
     * @param  bool  $employIfNeeded  Whether to employ newly added managers
     * @return Collection<int, Manager> Collection of newly added managers
     *
     * @example
     * ```php
     * $newManagers = collect([$paulHeyman]);
     * $addedManagers = $service->updateManagerRelationships($tagTeam, $newManagers, now(), true);
     * ```
     */
    public function updateManagerRelationships(
        TagTeam $tagTeam,
        Collection|array $newManagers,
        Carbon $changeDate,
        bool $employIfNeeded = false
    ): Collection {
        // Normalize to collection and validate
        $newManagersCollection = collect($newManagers)->ensure(Manager::class);

        // Get current manager relationships
        $currentManagers = $tagTeam->currentManagers;

        // Identify relationships to end and add
        $managersToRemove = $currentManagers->diff($newManagersCollection);
        $managersToAdd = $newManagersCollection->diff($currentManagers);

        // End old relationships
        if ($managersToRemove->isNotEmpty()) {
            $this->detachManagersFromTeam($tagTeam, $managersToRemove, $changeDate);
        }

        // Add new relationships
        if ($managersToAdd->isNotEmpty()) {
            $this->attachManagersToTeam($tagTeam, $managersToAdd, $changeDate);

            // Handle employment for new managers
            if ($employIfNeeded) {
                $this->employMembers($managersToAdd, $changeDate);
            }
        }

        return $managersToAdd;
    }

    /**
     * Add new partners to an existing tag team.
     *
     * This method adds new wrestlers as partners without affecting existing partnerships.
     * Useful for expanding tag teams or adding temporary members.
     *
     * @param  TagTeam  $tagTeam  The tag team to add partners to
     * @param  Collection<int, Wrestler>  $wrestlers  Wrestlers to add as partners
     * @param  Carbon  $joinDate  The partnership start date
     * @param  bool  $employIfNeeded  Whether to employ new partners
     * @return Collection<int, Wrestler> Collection of actually added partners
     *
     * @example
     * ```php
     * $newPartners = collect([$xavier]);
     * $addedPartners = $service->addPartners($tagTeam, $newPartners, now(), true);
     * ```
     */
    public function addPartners(
        TagTeam $tagTeam,
        Collection $wrestlers,
        Carbon $joinDate,
        bool $employIfNeeded = false
    ): Collection {
        // Validate input
        if ($wrestlers->isEmpty()) {
            throw new InvalidArgumentException('Cannot add partners: No wrestlers provided.');
        }

        $wrestlers->ensure(Wrestler::class);

        // Filter out existing partners
        $currentPartnerIds = $tagTeam->currentWrestlers->pluck('id');
        $newPartners = $wrestlers->reject(fn (Wrestler $wrestler) => $currentPartnerIds->contains($wrestler->id));

        if ($newPartners->isEmpty()) {
            throw new InvalidArgumentException('Cannot add partners: All provided wrestlers are already current partners.');
        }

        // Validate new partners
        $this->validateNewPartners($tagTeam, $newPartners);

        // Add new partners
        $this->attachWrestlersToTeam($tagTeam, $newPartners, $joinDate);

        // Handle employment
        if ($employIfNeeded) {
            $this->employMembers($newPartners, $joinDate);
        }

        return $newPartners;
    }

    /**
     * Employ members who are not already employed.
     *
     * This method handles employment for collections of wrestlers or managers,
     * filtering out those who are already employed to avoid conflicts.
     *
     * @param  Collection<int, Wrestler|Manager>  $members  Members to potentially employ
     * @param  Carbon  $employmentDate  The employment date
     *
     * @example
     * ```php
     * $service->employMembers($newPartners, now());
     * ```
     */
    public function employMembers(Collection $members, Carbon $employmentDate): void
    {
        foreach ($members as $member) {
            if (! $member->isEmployed()) {
                if ($member instanceof Wrestler) {
                    $this->wrestlersEmployAction->handle($member, $employmentDate);
                } elseif ($member instanceof Manager) {
                    $this->managersEmployAction->handle($member, $employmentDate);
                }
            }
        }
    }

    /**
     * Attach wrestlers to tag team with proper pivot data.
     *
     * @param  TagTeam  $tagTeam  The tag team
     * @param  Collection<int, Wrestler>  $wrestlers  Wrestlers to attach
     * @param  Carbon  $joinDate  The join date
     */
    private function attachWrestlersToTeam(TagTeam $tagTeam, Collection $wrestlers, Carbon $joinDate): void
    {
        foreach ($wrestlers as $wrestler) {
            $tagTeam->wrestlers()->attach($wrestler->id, [
                'joined_at' => $joinDate,
                'left_at' => null,
            ]);
        }
    }

    /**
     * Detach wrestlers from tag team with proper date tracking.
     *
     * @param  TagTeam  $tagTeam  The tag team
     * @param  Collection<int, Wrestler>  $wrestlers  Wrestlers to detach
     * @param  Carbon  $leftDate  The leave date
     */
    private function detachWrestlersFromTeam(TagTeam $tagTeam, Collection $wrestlers, Carbon $leftDate): void
    {
        foreach ($wrestlers as $wrestler) {
            $tagTeam->wrestlers()->updateExistingPivot($wrestler->id, [
                'left_at' => $leftDate,
            ]);
        }
    }

    /**
     * Attach managers to tag team with proper pivot data.
     *
     * @param  TagTeam  $tagTeam  The tag team
     * @param  Collection<int, Manager>  $managers  Managers to attach
     * @param  Carbon  $hiredDate  The hired date
     */
    private function attachManagersToTeam(TagTeam $tagTeam, Collection $managers, Carbon $hiredDate): void
    {
        foreach ($managers as $manager) {
            $tagTeam->managers()->attach($manager->id, [
                'hired_at' => $hiredDate,
                'fired_at' => null,
            ]);
        }
    }

    /**
     * Detach managers from tag team with proper date tracking.
     *
     * @param  TagTeam  $tagTeam  The tag team
     * @param  Collection<int, Manager>  $managers  Managers to detach
     * @param  Carbon  $firedDate  The fired date
     */
    private function detachManagersFromTeam(TagTeam $tagTeam, Collection $managers, Carbon $firedDate): void
    {
        foreach ($managers as $manager) {
            $tagTeam->managers()->updateExistingPivot($manager->id, [
                'fired_at' => $firedDate,
            ]);
        }
    }

    /**
     * Validate founding members for new tag team.
     *
     * @param  Collection<int, Wrestler>  $wrestlers  Founding wrestlers
     * @param  Collection<int, Manager>|null  $managers  Founding managers
     */
    private function validateFoundingMembers(Collection $wrestlers, ?Collection $managers): void
    {
        if ($wrestlers->count() < 2) {
            throw new InvalidArgumentException('Tag teams require at least 2 founding wrestlers for viable competition.');
        }

        $wrestlers->ensure(Wrestler::class);

        if ($managers) {
            $managers->ensure(Manager::class);
        }
    }

    /**
     * Validate new partners for business rule compliance.
     *
     * @param  TagTeam  $tagTeam  The tag team receiving new partners
     * @param  Collection<int, Wrestler>  $newWrestlers  New wrestlers to validate
     */
    private function validateNewPartners(TagTeam $tagTeam, Collection $newWrestlers): void
    {
        foreach ($newWrestlers as $wrestler) {
            // Check for conflicting tag team memberships
            $activeTeamMemberships = $wrestler->tagTeams()
                ->wherePivot('left_at', null)
                ->where('tag_team_id', '!=', $tagTeam->id)
                ->count();

            if ($activeTeamMemberships > 0) {
                throw new InvalidArgumentException("Cannot add partner: Wrestler '{$wrestler->name}' is already an active member of another tag team. End existing partnerships first.");
            }
        }
    }
}
