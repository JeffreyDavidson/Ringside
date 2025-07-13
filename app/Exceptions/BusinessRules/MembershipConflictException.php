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
class MembershipConflictException extends BaseBusinessException
{
    /**
     * Exception for wrestler already being in a stable.
     */
    public static function alreadyInStable(Model $wrestler, Model $currentStable, Model $newStable): static
    {
        $wrestlerName = $wrestler->name ?? "ID: {$wrestler->getKey()}";
        $currentStableName = $currentStable->name ?? "ID: {$currentStable->getKey()}";
        $newStableName = $newStable->name ?? "ID: {$newStable->getKey()}";

        /** @var static */
        return new self(
            "Wrestler '{$wrestlerName}' is already a member of stable '{$currentStableName}' and cannot join '{$newStableName}'. Remove from current stable first or choose different wrestler."
        );
    }

    /**
     * Exception for wrestler already being in a tag team.
     */
    public static function alreadyInTagTeam(Model $wrestler, Model $currentTeam, Model $newTeam): static
    {
        $wrestlerName = $wrestler->name ?? "ID: {$wrestler->getKey()}";
        $currentTeamName = $currentTeam->name ?? "ID: {$currentTeam->getKey()}";
        $newTeamName = $newTeam->name ?? "ID: {$newTeam->getKey()}";

        /** @var static */
        return new self(
            "Wrestler '{$wrestlerName}' is already a member of tag team '{$currentTeamName}' and cannot join '{$newTeamName}'. Remove from current team first or choose different wrestler."
        );
    }

    /**
     * Exception for tag team already being in a stable.
     */
    public static function tagTeamAlreadyInStable(Model $tagTeam, Model $currentStable, Model $newStable): static
    {
        $teamName = $tagTeam->name ?? "ID: {$tagTeam->getKey()}";
        $currentStableName = $currentStable->name ?? "ID: {$currentStable->getKey()}";
        $newStableName = $newStable->name ?? "ID: {$newStable->getKey()}";

        /** @var static */
        return new self(
            "Tag team '{$teamName}' is already a member of stable '{$currentStableName}' and cannot join '{$newStableName}'. Remove from current stable first or choose different team."
        );
    }

    /**
     * Exception for manager having too many clients.
     */
    public static function managerOverload(Model $manager, int $maxClients, int $currentClients): static
    {
        $managerName = $manager->name ?? "ID: {$manager->getKey()}";

        /** @var static */
        return new self(
            "Manager '{$managerName}' already manages {$currentClients} clients (maximum: {$maxClients}) and cannot take additional clients. Remove existing clients or choose different manager."
        );
    }

    /**
     * Exception for entity already having a manager.
     */
    public static function alreadyHasManager(Model $entity, Model $currentManager, Model $newManager): static
    {
        $entityName = $entity->name ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $currentManagerName = $currentManager->name ?? "ID: {$currentManager->getKey()}";
        $newManagerName = $newManager->name ?? "ID: {$newManager->getKey()}";

        /** @var static */
        return new self(
            "{$entityType} '{$entityName}' already has manager '{$currentManagerName}' and cannot be assigned to '{$newManagerName}'. Remove current manager first or choose different entity."
        );
    }

    /**
     * Exception for conflicting membership dates.
     */
    public static function overlappingMembership(Model $entity, Model $group1, Model $group2, Carbon $startDate, Carbon $endDate): static
    {
        $entityName = $entity->name ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $group1Name = $group1->name ?? "ID: {$group1->getKey()}";
        $group2Name = $group2->name ?? "ID: {$group2->getKey()}";

        /** @var static */
        return new self(
            "{$entityType} '{$entityName}' has overlapping membership in '{$group1Name}' and '{$group2Name}' during period {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}. Memberships cannot overlap."
        );
    }

    /**
     * Exception for stable having insufficient members.
     */
    public static function insufficientStableMembers(Model $stable, int $currentMembers, int $requiredMinimum): static
    {
        $stableName = $stable->name ?? "ID: {$stable->getKey()}";

        return new self(
            "Stable '{$stableName}' has {$currentMembers} members but requires minimum of {$requiredMinimum}. Add more members or modify stable requirements."
        );
    }

    /**
     * Exception for tag team having wrong number of members.
     */
    public static function invalidTagTeamSize(Model $tagTeam, int $currentMembers, int $requiredMembers): static
    {
        $teamName = $tagTeam->name ?? "ID: {$tagTeam->getKey()}";

        return new self(
            "Tag team '{$teamName}' has {$currentMembers} members but requires exactly {$requiredMembers}. Adjust membership to meet requirements."
        );
    }

    /**
     * Exception for circular membership dependencies.
     */
    public static function circularDependency(array $membershipChain): static
    {
        $chainDescription = implode(' -> ', array_map(function ($entity) {
            return ($entity['name'] ?? "ID: {$entity['id']}")." ({$entity['type']})";
        }, $membershipChain));

        return new self(
            "Circular membership dependency detected: {$chainDescription}. Resolve circular references in membership structure."
        );
    }

    /**
     * Exception for entity trying to manage itself.
     */
    public static function selfManagement(Model $entity): static
    {
        $entityName = $entity->name ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' cannot manage itself. Choose different manager or entity."
        );
    }

    /**
     * Exception for retired entity membership.
     */
    public static function retiredEntityMembership(Model $entity, Model $group): static
    {
        $entityName = $entity->name ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->name ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new self(
            "Retired {$entityType} '{$entityName}' cannot join {$groupType} '{$groupName}'. Only active entities can have new memberships."
        );
    }

    /**
     * Exception for suspended entity membership.
     */
    public static function suspendedEntityMembership(Model $entity, Model $group): static
    {
        $entityName = $entity->name ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->name ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new self(
            "Suspended {$entityType} '{$entityName}' cannot join {$groupType} '{$groupName}'. Resolve suspension before adding new memberships."
        );
    }

    /**
     * Exception for exclusive membership violation.
     */
    public static function exclusiveMembershipViolation(Model $entity, Model $exclusiveGroup, Model $newGroup): static
    {
        $entityName = $entity->name ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $exclusiveGroupName = $exclusiveGroup->name ?? "ID: {$exclusiveGroup->getKey()}";
        $newGroupName = $newGroup->name ?? "ID: {$newGroup->getKey()}";

        return new self(
            "{$entityType} '{$entityName}' has exclusive membership in '{$exclusiveGroupName}' and cannot join '{$newGroupName}'. End exclusive membership first."
        );
    }

    /**
     * Exception for gender-specific membership violations.
     */
    public static function genderRestriction(Model $entity, Model $group, string $requiredGender, string $entityGender): static
    {
        $entityName = $entity->name ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->name ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new self(
            "{$entityType} '{$entityName}' (gender: {$entityGender}) cannot join {$groupType} '{$groupName}' which requires {$requiredGender} members only."
        );
    }

    /**
     * Exception for age-based membership restrictions.
     */
    public static function ageRestriction(Model $entity, Model $group, int $entityAge, int $minimumAge): static
    {
        $entityName = $entity->name ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->name ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new self(
            "{$entityType} '{$entityName}' (age: {$entityAge}) cannot join {$groupType} '{$groupName}' which requires minimum age of {$minimumAge}."
        );
    }

    /**
     * Exception for experience level restrictions.
     */
    public static function experienceRestriction(Model $entity, Model $group, string $entityLevel, string $requiredLevel): static
    {
        $entityName = $entity->name ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->name ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new self(
            "{$entityType} '{$entityName}' (experience: {$entityLevel}) cannot join {$groupType} '{$groupName}' which requires {$requiredLevel} experience level."
        );
    }

    /**
     * Exception for conflicting contractual obligations.
     */
    public static function contractualConflict(Model $entity, Model $group, string $conflictDetails): static
    {
        $entityName = $entity->name ?? "ID: {$entity->getKey()}";
        $entityType = class_basename($entity);
        $groupName = $group->name ?? "ID: {$group->getKey()}";
        $groupType = class_basename($group);

        return new self(
            "{$entityType} '{$entityName}' cannot join {$groupType} '{$groupName}' due to contractual conflict: {$conflictDetails}"
        );
    }
}
