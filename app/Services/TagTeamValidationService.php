<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TagTeams\TagTeamData;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Service for validating tag team business rules and data integrity.
 *
 * This service centralizes all tag team validation logic to ensure consistent
 * business rule enforcement across all tag team operations. It provides
 * comprehensive validation for names, members, relationships, and business
 * constraints that maintain data integrity and operational consistency.
 *
 * BUSINESS CONTEXT:
 * Tag team operations require complex validation including uniqueness constraints,
 * member availability checking, partnership conflict detection, and business
 * rule compliance. This service ensures all validations are applied consistently
 * and provides clear error messages for business rule violations.
 *
 * VALIDATION AREAS:
 * - Name uniqueness and formatting validation
 * - Member availability and conflict checking
 * - Partnership exclusivity and business rules
 * - Data integrity and relationship constraints
 * - Employment and status validation
 *
 * DESIGN PATTERN:
 * Service pattern - Centralizes validation logic away from Actions
 * Strategy pattern - Different validation strategies for different contexts
 * Template method - Consistent validation workflow with customizable rules
 *
 * @example
 * ```php
 * $service = app(TagTeamValidationService::class);
 *
 * // Validate tag team creation
 * $service->validateForCreation($tagTeamData);
 *
 * // Validate name uniqueness
 * $service->validateUniqueName('The Hardy Boyz');
 *
 * // Validate member availability
 * $service->validateMembersAvailable($wrestlers, $managers);
 * ```
 */
class TagTeamValidationService
{
    /**
     * Validate tag team data for creation.
     *
     * This method performs comprehensive validation for new tag team creation
     * including name validation, member availability, and business rule compliance.
     *
     * @param  TagTeamData  $tagTeamData  The tag team data to validate
     * @throws InvalidArgumentException When validation fails
     *
     * @example
     * ```php
     * $service->validateForCreation($tagTeamData);
     * ```
     */
    public function validateForCreation(TagTeamData $tagTeamData): void
    {
        // Validate basic data integrity
        $this->validateBasicData($tagTeamData);

        // Validate name uniqueness
        $this->validateUniqueName($tagTeamData->name);

        // Validate founding members
        $this->validateFoundingMembers($tagTeamData);

        // Validate member availability
        $wrestlers = collect([$tagTeamData->wrestlerA, $tagTeamData->wrestlerB])->filter();
        $managers = $tagTeamData->managers ?? collect();
        $this->validateMembersAvailable($wrestlers, $managers);
    }

    /**
     * Validate tag team data for update.
     *
     * This method performs comprehensive validation for tag team updates
     * including name uniqueness (excluding current team), member availability,
     * and business rule compliance for membership changes.
     *
     * @param  TagTeam  $tagTeam  The existing tag team being updated
     * @param  TagTeamData  $tagTeamData  The updated tag team data
     * @throws InvalidArgumentException When validation fails
     *
     * @example
     * ```php
     * $service->validateForUpdate($existingTeam, $updatedData);
     * ```
     */
    public function validateForUpdate(TagTeam $tagTeam, TagTeamData $tagTeamData): void
    {
        // Validate basic data integrity
        $this->validateBasicData($tagTeamData);

        // Validate name uniqueness (excluding current team)
        $this->validateUniqueName($tagTeamData->name, $tagTeam);

        // Validate updated members
        $wrestlers = collect([$tagTeamData->wrestlerA, $tagTeamData->wrestlerB])->filter();
        $managers = $tagTeamData->managers ?? collect();

        // Only validate availability for actually new members
        $newWrestlers = $wrestlers->diff($tagTeam->currentWrestlers);
        $newManagers = $managers->diff($tagTeam->currentManagers);

        if ($newWrestlers->isNotEmpty() || $newManagers->isNotEmpty()) {
            $this->validateMembersAvailable($newWrestlers, $newManagers);
        }

        // Validate partnership requirements
        $this->validatePartnershipRequirements($wrestlers);
    }

    /**
     * Validate tag team name uniqueness.
     *
     * Ensures the tag team name is unique across all active (non-deleted) tag teams.
     * Optionally excludes a specific tag team from the uniqueness check (for updates).
     *
     * @param  string  $name  The tag team name to validate
     * @param  TagTeam|null  $excludeTeam  Tag team to exclude from uniqueness check
     * @throws InvalidArgumentException When name is not unique
     *
     * @example
     * ```php
     * // For creation
     * $service->validateUniqueName('The Hardy Boyz');
     *
     * // For update (exclude current team)
     * $service->validateUniqueName('The Hardy Boyz', $existingTeam);
     * ```
     */
    public function validateUniqueName(string $name, ?TagTeam $excludeTeam = null): void
    {
        $trimmedName = mb_trim($name);

        if (empty($trimmedName)) {
            throw new InvalidArgumentException('Tag team name cannot be empty.');
        }

        $query = TagTeam::where('name', $trimmedName);

        if ($excludeTeam) {
            $query->where('id', '!=', $excludeTeam->id);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException("Tag team name '{$trimmedName}' is already taken. Please choose a different name.");
        }
    }

    /**
     * Validate member availability for tag team operations.
     *
     * Checks that all provided wrestlers and managers are available for tag team
     * membership without conflicts with existing commitments or exclusivity requirements.
     *
     * @param  Collection<int, Wrestler>  $wrestlers  Wrestlers to validate
     * @param  Collection<int, Manager>  $managers  Managers to validate
     * @throws InvalidArgumentException When members are not available
     *
     * @example
     * ```php
     * $service->validateMembersAvailable($wrestlers, $managers);
     * ```
     */
    public function validateMembersAvailable(Collection $wrestlers, Collection $managers): void
    {
        // Validate wrestler availability
        foreach ($wrestlers as $wrestler) {
            $this->validateWrestlerAvailable($wrestler);
        }

        // Validate manager availability
        foreach ($managers as $manager) {
            $this->validateManagerAvailable($manager);
        }
    }

    /**
     * Validate wrestler availability for tag team membership.
     *
     * Checks that a wrestler is available for tag team membership including
     * exclusivity constraints, existing commitments, and business rules.
     *
     * @param  Wrestler  $wrestler  The wrestler to validate
     * @throws InvalidArgumentException When wrestler is not available
     *
     * @example
     * ```php
     * $service->validateWrestlerAvailable($wrestler);
     * ```
     */
    public function validateWrestlerAvailable(Wrestler $wrestler): void
    {
        // Check for existing tag team memberships
        $activeTeamMemberships = $wrestler->tagTeams()
            ->wherePivot('left_at', null)
            ->count();

        if ($activeTeamMemberships > 0) {
            throw new InvalidArgumentException("Wrestler '{$wrestler->name}' is already an active member of another tag team. End existing partnerships before adding to new team.");
        }

        // Check for exclusivity conflicts (if applicable)
        if (method_exists($wrestler, 'hasExclusivityConflicts') && $wrestler->hasExclusivityConflicts()) {
            throw new InvalidArgumentException("Wrestler '{$wrestler->name}' has exclusivity conflicts that prevent tag team membership.");
        }

        // Check for injury or unavailability status
        if (method_exists($wrestler, 'isInjured') && $wrestler->isInjured()) {
            throw new InvalidArgumentException("Wrestler '{$wrestler->name}' is currently injured and cannot join tag teams.");
        }
    }

    /**
     * Validate manager availability for tag team management.
     *
     * Checks that a manager is available for tag team management including
     * capacity constraints, existing commitments, and business rules.
     *
     * @param  Manager  $manager  The manager to validate
     * @throws InvalidArgumentException When manager is not available
     *
     * @example
     * ```php
     * $service->validateManagerAvailable($manager);
     * ```
     */
    public function validateManagerAvailable(Manager $manager): void
    {
        // Check manager capacity constraints (if implemented)
        if (method_exists($manager, 'hasCapacityFor')) {
            if (! $manager->hasCapacityFor('tag_team')) {
                throw new InvalidArgumentException("Manager '{$manager->name}' has reached capacity and cannot manage additional tag teams.");
            }
        }

        // Check for exclusivity conflicts (if applicable)
        if (method_exists($manager, 'hasExclusivityConflicts') && $manager->hasExclusivityConflicts()) {
            throw new InvalidArgumentException("Manager '{$manager->name}' has exclusivity conflicts that prevent tag team management.");
        }
    }

    /**
     * Validate partnership requirements for tag teams.
     *
     * Ensures that the provided wrestlers meet the minimum requirements for
     * forming a viable tag team partnership.
     *
     * @param  Collection<int, Wrestler>  $wrestlers  The wrestlers forming the partnership
     * @throws InvalidArgumentException When partnership requirements are not met
     *
     * @example
     * ```php
     * $service->validatePartnershipRequirements($wrestlers);
     * ```
     */
    public function validatePartnershipRequirements(Collection $wrestlers): void
    {
        if ($wrestlers->count() < 2) {
            throw new InvalidArgumentException('Tag teams require a minimum of 2 wrestlers for viable competition.');
        }

        if ($wrestlers->count() > 6) {
            throw new InvalidArgumentException('Tag teams cannot have more than 6 active partners due to competition regulations.');
        }

        // Validate no duplicate wrestlers
        $uniqueIds = $wrestlers->pluck('id')->unique();
        if ($uniqueIds->count() !== $wrestlers->count()) {
            throw new InvalidArgumentException('Cannot add the same wrestler multiple times to a tag team.');
        }
    }

    /**
     * Validate basic tag team data integrity.
     *
     * Performs fundamental validation of tag team data including required fields,
     * data types, and basic business rules.
     *
     * @param  TagTeamData  $tagTeamData  The tag team data to validate
     * @throws InvalidArgumentException When basic data validation fails
     */
    private function validateBasicData(TagTeamData $tagTeamData): void
    {
        // Validate required name
        if (empty(mb_trim($tagTeamData->name))) {
            throw new InvalidArgumentException('Tag team name is required and cannot be empty.');
        }

        // Validate name length
        if (mb_strlen(mb_trim($tagTeamData->name)) > 100) {
            throw new InvalidArgumentException('Tag team name cannot exceed 100 characters.');
        }

        // Validate wrestlers are provided
        if (! $tagTeamData->wrestlerA || ! $tagTeamData->wrestlerB) {
            throw new InvalidArgumentException('Tag teams require exactly 2 founding wrestlers (wrestlerA and wrestlerB).');
        }

        // Validate wrestlers are different
        if ($tagTeamData->wrestlerA->id === $tagTeamData->wrestlerB->id) {
            throw new InvalidArgumentException('Tag team partners must be different wrestlers.');
        }

        // Validate signature move length if provided
        if ($tagTeamData->signature_move && mb_strlen($tagTeamData->signature_move) > 255) {
            throw new InvalidArgumentException('Signature move description cannot exceed 255 characters.');
        }
    }

    /**
     * Validate founding members for new tag team creation.
     *
     * Performs specific validation for founding members including minimum
     * requirements and business rule compliance.
     *
     * @param  TagTeamData  $tagTeamData  The tag team data to validate
     * @throws InvalidArgumentException When founding member validation fails
     */
    private function validateFoundingMembers(TagTeamData $tagTeamData): void
    {
        // Validate founding wrestlers
        if (! $tagTeamData->wrestlerA instanceof Wrestler) {
            throw new InvalidArgumentException('WrestlerA must be a valid Wrestler instance.');
        }

        if (! $tagTeamData->wrestlerB instanceof Wrestler) {
            throw new InvalidArgumentException('WrestlerB must be a valid Wrestler instance.');
        }

        // Validate managers if provided
        if ($tagTeamData->managers) {
            $tagTeamData->managers->ensure(Manager::class);

            if ($tagTeamData->managers->count() > 3) {
                throw new InvalidArgumentException('Tag teams cannot have more than 3 managers at formation.');
            }
        }
    }
}
