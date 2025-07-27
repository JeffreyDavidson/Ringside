<?php

declare(strict_types=1);

namespace App\Exceptions\BusinessRules;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when membership conflicts occur in wrestling promotion management.
 *
 * This exception handles various membership conflict scenarios including
 * stable membership, tag team partnerships, and management relationships
 * that violate business rules or create logical inconsistencies.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions have complex membership rules governing stables,
 * tag teams, and management relationships. These rules prevent conflicts
 * like wrestlers being in multiple stables simultaneously or tag teams
 * having conflicting partnerships.
 *
 * COMMON SCENARIOS:
 * - Wrestler already in a stable
 * - Tag team member conflicts
 * - Manager assignment conflicts
 * - Overlapping membership periods
 *
 * @example
 * ```php
 * // Stable membership conflict
 * throw MembershipConflictException::alreadyInStable($wrestler, $currentStable, $newStable);
 *
 * // Tag team conflict
 * throw MembershipConflictException::alreadyInTagTeam($wrestler, $currentTeam, $newTeam);
 *
 * // Manager conflict
 * throw MembershipConflictException::managerOverload($manager, $maxClients, $currentClients);
 * ```
 */
final class MembershipConflictException extends BaseBusinessException
{
    /**
     * Wrestler is already a member of a stable and cannot join another.
     *
     * @param  Model  $wrestler  The wrestler with existing stable membership
     * @param  Model  $currentStable  The stable the wrestler currently belongs to
     * @param  Model  $newStable  The stable the wrestler cannot join
     */
    public static function alreadyInStable(Model $wrestler, Model $currentStable, Model $newStable): static
    {
        $wrestlerContext = self::formatModelContext($wrestler);
        $currentStableContext = self::formatModelContext($currentStable);
        $newStableContext = self::formatModelContext($newStable);

        return new self(
            "{$wrestlerContext} is already a member of {$currentStableContext} and cannot join {$newStableContext}. Remove from current stable first or choose different wrestler."
        );
    }

    /**
     * Wrestler is already a member of a tag team and cannot join another.
     *
     * @param  Model  $wrestler  The wrestler with existing tag team membership
     * @param  Model  $currentTeam  The tag team the wrestler currently belongs to
     * @param  Model  $newTeam  The tag team the wrestler cannot join
     */
    public static function alreadyInTagTeam(Model $wrestler, Model $currentTeam, Model $newTeam): static
    {
        $wrestlerContext = self::formatModelContext($wrestler);
        $currentTeamContext = self::formatModelContext($currentTeam);
        $newTeamContext = self::formatModelContext($newTeam);

        return new static(
            "{$wrestlerContext} is already a member of {$currentTeamContext} and cannot join {$newTeamContext}. Remove from current team first or choose different wrestler."
        );
    }

    /**
     * Tag team is already a member of a stable and cannot join another.
     *
     * @param  Model  $tagTeam  The tag team with existing stable membership
     * @param  Model  $currentStable  The stable the tag team currently belongs to
     * @param  Model  $newStable  The stable the tag team cannot join
     */
    public static function tagTeamAlreadyInStable(Model $tagTeam, Model $currentStable, Model $newStable): static
    {
        $tagTeamContext = self::formatModelContext($tagTeam);
        $currentStableContext = self::formatModelContext($currentStable);
        $newStableContext = self::formatModelContext($newStable);

        return new static(
            "{$tagTeamContext} is already a member of {$currentStableContext} and cannot join {$newStableContext}. Remove from current stable first or choose different team."
        );
    }

    /**
     * Manager has reached maximum client capacity and cannot take additional clients.
     *
     * @param  Model  $manager  The manager at maximum capacity
     * @param  int  $maxClients  Maximum number of clients allowed
     * @param  int  $currentClients  Current number of clients
     */
    public static function managerOverload(Model $manager, int $maxClients, int $currentClients): static
    {
        $managerContext = self::formatModelContext($manager);

        return new static(
            "{$managerContext} already manages {$currentClients} clients (maximum: {$maxClients}) and cannot take additional clients. Remove existing clients or choose different manager."
        );
    }

    /**
     * Entity already has a manager and cannot be assigned to another.
     *
     * NOTE: Multiple managers are generally allowed in the system. This exception
     * should only be used in specific contexts where single manager restriction applies.
     *
     * @param  Model  $entity  The entity with existing manager
     * @param  Model  $currentManager  The entity's current manager
     * @param  Model  $newManager  The manager that cannot be assigned
     */
    public static function alreadyHasManager(Model $entity, Model $currentManager, Model $newManager): static
    {
        $entityContext = self::formatModelContext($entity);
        $currentManagerContext = self::formatModelContext($currentManager);
        $newManagerContext = self::formatModelContext($newManager);

        return new static(
            "{$entityContext} already has manager {$currentManagerContext} and cannot be assigned to {$newManagerContext}. Remove current manager first or choose different entity."
        );
    }

    /**
     * Entity has conflicting membership dates with overlapping periods.
     *
     * @param  Model  $entity  The entity with overlapping membership
     * @param  Model  $group1  First group in the membership conflict
     * @param  Model  $group2  Second group in the membership conflict
     * @param  Carbon  $startDate  Start date of the overlapping period
     * @param  Carbon  $endDate  End date of the overlapping period
     */
    public static function overlappingMembership(Model $entity, Model $group1, Model $group2, Carbon $startDate, Carbon $endDate): static
    {
        $entityContext = self::formatModelContext($entity);
        $group1Context = self::formatModelContext($group1);
        $group2Context = self::formatModelContext($group2);

        return new static(
            "{$entityContext} has overlapping membership in {$group1Context} and {$group2Context} during period {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}. Memberships cannot overlap."
        );
    }

    /**
     * Stable has insufficient members to meet operational requirements.
     *
     * @param  Model  $stable  The stable with insufficient membership
     * @param  int  $currentMembers  Current number of active members
     * @param  int  $requiredMinimum  Minimum required members for operation
     */
    public static function insufficientStableMembers(Model $stable, int $currentMembers, int $requiredMinimum): static
    {
        $stableContext = self::formatModelContext($stable);

        return new static(
            "{$stableContext} has {$currentMembers} members but requires minimum of {$requiredMinimum}. Add more members or modify stable requirements."
        );
    }

    /**
     * Tag team has incorrect number of members for operational requirements.
     *
     * @param  Model  $tagTeam  The tag team with invalid member count
     * @param  int  $currentMembers  Current number of team members
     * @param  int  $requiredMembers  Required number of team members
     */
    public static function invalidTagTeamSize(Model $tagTeam, int $currentMembers, int $requiredMembers): static
    {
        $tagTeamContext = self::formatModelContext($tagTeam);

        return new static(
            "{$tagTeamContext} has {$currentMembers} members but requires exactly {$requiredMembers}. Adjust membership to meet requirements."
        );
    }

    /**
     * Exception for circular membership dependencies.
     *
     * @param  array<int, array{id: int, name: string|null, type: string}>  $membershipChain
     */
    public static function circularDependency(array $membershipChain): static
    {
        $chainDescription = implode(' -> ', array_map(function (array $entity) {
            return ($entity['name'] ?? "ID: {$entity['id']}")." ({$entity['type']})";
        }, $membershipChain));

        return new static(
            "Circular membership dependency detected: {$chainDescription}. Resolve circular references in membership structure."
        );
    }

    /**
     * Entity cannot establish management relationship with itself.
     *
     * @param  Model  $entity  The entity attempting self-management
     */
    public static function selfManagement(Model $entity): static
    {
        $entityContext = self::formatModelContext($entity);

        return new static(
            "{$entityContext} cannot manage itself. Choose different manager or entity."
        );
    }

    /**
     * Retired entity cannot establish new group memberships.
     *
     * @param  Model  $entity  The retired entity attempting membership
     * @param  Model  $group  The group the entity cannot join
     */
    public static function retiredEntityMembership(Model $entity, Model $group): static
    {
        $entityContext = self::formatModelContext($entity);
        $groupContext = self::formatModelContext($group);

        return new static(
            "Retired {$entityContext} cannot join {$groupContext}. Only active entities can have new memberships."
        );
    }

    /**
     * Suspended entity cannot establish new group memberships.
     *
     * @param  Model  $entity  The suspended entity attempting membership
     * @param  Model  $group  The group the entity cannot join
     */
    public static function suspendedEntityMembership(Model $entity, Model $group): static
    {
        $entityContext = self::formatModelContext($entity);
        $groupContext = self::formatModelContext($group);

        return new static(
            "Suspended {$entityContext} cannot join {$groupContext}. Resolve suspension before adding new memberships."
        );
    }

    /**
     * Entity has exclusive membership that prevents joining additional groups.
     *
     * @param  Model  $entity  The entity with exclusive membership
     * @param  Model  $exclusiveGroup  The group with exclusive membership
     * @param  Model  $newGroup  The group the entity cannot join
     */
    public static function exclusiveMembershipViolation(Model $entity, Model $exclusiveGroup, Model $newGroup): static
    {
        $entityContext = self::formatModelContext($entity);
        $exclusiveGroupContext = self::formatModelContext($exclusiveGroup);
        $newGroupContext = self::formatModelContext($newGroup);

        return new static(
            "{$entityContext} has exclusive membership in {$exclusiveGroupContext} and cannot join {$newGroupContext}. End exclusive membership first."
        );
    }

    /**
     * Entity cannot join group due to gender-specific membership restrictions.
     *
     * @param  Model  $entity  The entity with incompatible gender
     * @param  Model  $group  The group with gender restrictions
     * @param  string  $requiredGender  Required gender for group membership
     * @param  string  $entityGender  Entity's gender
     */
    public static function genderRestriction(Model $entity, Model $group, string $requiredGender, string $entityGender): static
    {
        $entityContext = self::formatModelContext($entity);
        $groupContext = self::formatModelContext($group);

        return new static(
            "{$entityContext} (gender: {$entityGender}) cannot join {$groupContext} which requires {$requiredGender} members only."
        );
    }

    /**
     * Entity cannot join group due to age-based membership restrictions.
     *
     * @param  Model  $entity  The entity with insufficient age
     * @param  Model  $group  The group with age restrictions
     * @param  int  $entityAge  Entity's current age
     * @param  int  $minimumAge  Minimum age required for membership
     */
    public static function ageRestriction(Model $entity, Model $group, int $entityAge, int $minimumAge): static
    {
        $entityContext = self::formatModelContext($entity);
        $groupContext = self::formatModelContext($group);

        return new static(
            "{$entityContext} (age: {$entityAge}) cannot join {$groupContext} which requires minimum age of {$minimumAge}."
        );
    }

    /**
     * Entity cannot join group due to experience level restrictions.
     *
     * @param  Model  $entity  The entity with insufficient experience
     * @param  Model  $group  The group with experience requirements
     * @param  string  $entityLevel  Entity's current experience level
     * @param  string  $requiredLevel  Required experience level for membership
     */
    public static function experienceRestriction(Model $entity, Model $group, string $entityLevel, string $requiredLevel): static
    {
        $entityContext = self::formatModelContext($entity);
        $groupContext = self::formatModelContext($group);

        return new static(
            "{$entityContext} (experience: {$entityLevel}) cannot join {$groupContext} which requires {$requiredLevel} experience level."
        );
    }

    /**
     * Entity cannot join group due to conflicting contractual obligations.
     *
     * @param  Model  $entity  The entity with contractual conflicts
     * @param  Model  $group  The group with conflicting requirements
     * @param  string  $conflictDetails  Description of the contractual conflict
     */
    public static function contractualConflict(Model $entity, Model $group, string $conflictDetails): static
    {
        $entityContext = self::formatModelContext($entity);
        $groupContext = self::formatModelContext($group);

        return new static(
            "{$entityContext} cannot join {$groupContext} due to contractual conflict: {$conflictDetails}"
        );
    }
}
