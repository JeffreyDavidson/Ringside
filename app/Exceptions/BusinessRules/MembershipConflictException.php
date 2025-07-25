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
     * Exception for wrestler already being in a stable.
     */
    public static function alreadyInStable(Model $wrestler, Model $currentStable, Model $newStable): static
    {
        $wrestlerName = $wrestler->getAttribute('name') ?? "ID: {$wrestler->getKey()}";
        $currentStableName = $currentStable->getAttribute('name') ?? "ID: {$currentStable->getKey()}";
        $newStableName = $newStable->getAttribute('name') ?? "ID: {$newStable->getKey()}";

        return new static(
            "Wrestler '{$wrestlerName}' is already a member of stable '{$currentStableName}' and cannot join '{$newStableName}'. Remove from current stable first or choose different wrestler."
        );
    }

    /**
     * Exception for wrestler already being in a tag team.
     */
    public static function alreadyInTagTeam(Model $wrestler, Model $currentTeam, Model $newTeam): static
    {
        $wrestlerName = $wrestler->getAttribute('name') ?? "ID: {$wrestler->getKey()}";
        $currentTeamName = $currentTeam->getAttribute('name') ?? "ID: {$currentTeam->getKey()}";
        $newTeamName = $newTeam->getAttribute('name') ?? "ID: {$newTeam->getKey()}";

        return new static(
            "Wrestler '{$wrestlerName}' is already a member of tag team '{$currentTeamName}' and cannot join '{$newTeamName}'. Remove from current team first or choose different wrestler."
        );
    }

    /**
     * Exception for tag team already being in a stable.
     */
    public static function tagTeamAlreadyInStable(Model $tagTeam, Model $currentStable, Model $newStable): static
    {
        $teamName = $tagTeam->getAttribute('name') ?? "ID: {$tagTeam->getKey()}";
        $currentStableName = $currentStable->getAttribute('name') ?? "ID: {$currentStable->getKey()}";
        $newStableName = $newStable->getAttribute('name') ?? "ID: {$newStable->getKey()}";

        return new static(
            "Tag team '{$teamName}' is already a member of stable '{$currentStableName}' and cannot join '{$newStableName}'. Remove from current stable first or choose different team."
        );
    }

    /**
     * Exception for manager having too many clients.
     */
    public static function managerOverload(Model $manager, int $maxClients, int $currentClients): static
    {
        $managerName = $manager->getAttribute('name') ?? "ID: {$manager->getKey()}";

        return new static(
            "Manager '{$managerName}' already manages {$currentClients} clients (maximum: {$maxClients}) and cannot take additional clients. Remove existing clients or choose different manager."
        );
    }

    /**
     * Exception for entity already having a manager.
     *
     * NOTE: Multiple managers are generally allowed in the system. This exception
     * should only be used in specific contexts where single manager restriction applies.
     */
    public static function alreadyHasManager(Model $entity, Model $currentManager, Model $newManager): static
    {
        $entityName = $entity->getAttribute('name') ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $currentManagerName = $currentManager->getAttribute('name') ?? "ID: {$currentManager->getKey()}";
        $newManagerName = $newManager->getAttribute('name') ?? "ID: {$newManager->getKey()}";

        return new static(
            "{$entityType} '{$entityName}' already has manager '{$currentManagerName}' and cannot be assigned to '{$newManagerName}'. Remove current manager first or choose different entity."
        );
    }

    /**
     * Exception for conflicting membership dates.
     */
    public static function overlappingMembership(Model $entity, Model $group1, Model $group2, Carbon $startDate, Carbon $endDate): static
    {
        $entityName = $entity->getAttribute('name') ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $group1Name = $group1->getAttribute('name') ?? "ID: {$group1->getKey()}";
        $group2Name = $group2->getAttribute('name') ?? "ID: {$group2->getKey()}";

        return new static(
            "{$entityType} '{$entityName}' has overlapping membership in '{$group1Name}' and '{$group2Name}' during period {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}. Memberships cannot overlap."
        );
    }

    /**
     * Exception for stable having insufficient members.
     */
    public static function insufficientStableMembers(Model $stable, int $currentMembers, int $requiredMinimum): static
    {
        $stableName = $stable->getAttribute('name') ?? "ID: {$stable->getKey()}";

        return new static(
            "Stable '{$stableName}' has {$currentMembers} members but requires minimum of {$requiredMinimum}. Add more members or modify stable requirements."
        );
    }

    /**
     * Exception for tag team having wrong number of members.
     */
    public static function invalidTagTeamSize(Model $tagTeam, int $currentMembers, int $requiredMembers): static
    {
        $teamName = $tagTeam->getAttribute('name') ?? "ID: {$tagTeam->getKey()}";

        return new static(
            "Tag team '{$teamName}' has {$currentMembers} members but requires exactly {$requiredMembers}. Adjust membership to meet requirements."
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
     * Exception for entity trying to manage itself.
     */
    public static function selfManagement(Model $entity): static
    {
        $entityName = $entity->getAttribute('name') ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);

        return new static(
            "{$entityType} '{$entityName}' cannot manage itself. Choose different manager or entity."
        );
    }

    /**
     * Exception for retired entity membership.
     */
    public static function retiredEntityMembership(Model $entity, Model $group): static
    {
        $entityName = $entity->getAttribute('name') ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->getAttribute('name') ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new static(
            "Retired {$entityType} '{$entityName}' cannot join {$groupType} '{$groupName}'. Only active entities can have new memberships."
        );
    }

    /**
     * Exception for suspended entity membership.
     */
    public static function suspendedEntityMembership(Model $entity, Model $group): static
    {
        $entityName = $entity->getAttribute('name') ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->getAttribute('name') ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new static(
            "Suspended {$entityType} '{$entityName}' cannot join {$groupType} '{$groupName}'. Resolve suspension before adding new memberships."
        );
    }

    /**
     * Exception for exclusive membership violation.
     */
    public static function exclusiveMembershipViolation(Model $entity, Model $exclusiveGroup, Model $newGroup): static
    {
        $entityName = $entity->getAttribute('name') ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $exclusiveGroupName = $exclusiveGroup->getAttribute('name') ?? "ID: {$exclusiveGroup->getKey()}";
        $newGroupName = $newGroup->getAttribute('name') ?? "ID: {$newGroup->getKey()}";

        return new static(
            "{$entityType} '{$entityName}' has exclusive membership in '{$exclusiveGroupName}' and cannot join '{$newGroupName}'. End exclusive membership first."
        );
    }

    /**
     * Exception for gender-specific membership violations.
     */
    public static function genderRestriction(Model $entity, Model $group, string $requiredGender, string $entityGender): static
    {
        $entityName = $entity->getAttribute('name') ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->getAttribute('name') ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new static(
            "{$entityType} '{$entityName}' (gender: {$entityGender}) cannot join {$groupType} '{$groupName}' which requires {$requiredGender} members only."
        );
    }

    /**
     * Exception for age-based membership restrictions.
     */
    public static function ageRestriction(Model $entity, Model $group, int $entityAge, int $minimumAge): static
    {
        $entityName = $entity->getAttribute('name') ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->getAttribute('name') ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new static(
            "{$entityType} '{$entityName}' (age: {$entityAge}) cannot join {$groupType} '{$groupName}' which requires minimum age of {$minimumAge}."
        );
    }

    /**
     * Exception for experience level restrictions.
     */
    public static function experienceRestriction(Model $entity, Model $group, string $entityLevel, string $requiredLevel): static
    {
        $entityName = $entity->getAttribute('name') ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->getAttribute('name') ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new static(
            "{$entityType} '{$entityName}' (experience: {$entityLevel}) cannot join {$groupType} '{$groupName}' which requires {$requiredLevel} experience level."
        );
    }

    /**
     * Exception for conflicting contractual obligations.
     */
    public static function contractualConflict(Model $entity, Model $group, string $conflictDetails): static
    {
        $entityName = $entity->getAttribute('name') ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->getAttribute('name') ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new static(
            "{$entityType} '{$entityName}' cannot join {$groupType} '{$groupName}' due to contractual conflict: {$conflictDetails}"
        );
    }
}
