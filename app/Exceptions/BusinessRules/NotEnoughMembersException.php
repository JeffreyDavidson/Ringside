<?php

declare(strict_types=1);

namespace App\Exceptions\BusinessRules;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

/**
 * Exception thrown when an entity does not have sufficient members to perform an operation.
 *
 * This exception handles scenarios where business operations require minimum member counts
 * that are not met by the current entity configuration.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions have strict rules about minimum participants for different entity types.
 * Tag teams require exactly 2 wrestlers, stables need minimum members for credibility,
 * and certain operations require sufficient active participants.
 *
 * COMMON SCENARIOS:
 * - Tag teams attempting to operate with fewer than required wrestlers
 * - Stables trying to activate without minimum member threshold
 * - Match assignments lacking sufficient competitors
 * - Championship defenses without proper challenger count
 *
 * BUSINESS IMPACT:
 * - Maintains competition integrity and regulatory compliance
 * - Protects fan expectations and storyline credibility
 * - Ensures proper match structure and competitive balance
 * - Prevents booking errors that could damage promotion reputation
 */
class NotEnoughMembersException extends BaseBusinessException
{
    /**
     * Tag team does not have the required number of wrestlers.
     *
     * @param  string|null  $tagTeamName  Optional tag team name for specific error messaging
     * @param  int|null  $currentCount  Optional current wrestler count for detailed messaging
     */
    public static function forTagTeam(?string $tagTeamName = null, ?int $currentCount = null): static
    {
        $context = $tagTeamName ? " '{$tagTeamName}'" : '';
        $requiredCount = TagTeam::NUMBER_OF_WRESTLERS_ON_TEAM;

        if ($currentCount !== null) {
            /** @var static */
            return new self(sprintf(
                'Tag team%s has %d wrestlers but requires exactly %d wrestlers to operate.',
                $context,
                $currentCount,
                $requiredCount
            ));
        }

        return new self(sprintf(
            'Tag team%s must contain exactly %d wrestlers to operate.',
            $context,
            $requiredCount
        ));
    }

    /**
     * Stable does not have the minimum required number of members.
     *
     * @param  int  $minimumRequired  Minimum number of members required
     * @param  int  $currentCount  Current number of active members
     * @param  string|null  $stableName  Optional stable name for specific error messaging
     */
    public static function forStable(int $minimumRequired, int $currentCount, ?string $stableName = null): static
    {
        $context = $stableName ? " '{$stableName}'" : '';

        return new self(sprintf(
            'Stable%s has %d members but requires at least %d members to operate.',
            $context,
            $currentCount,
            $minimumRequired
        ));
    }

    /**
     * Match does not have sufficient competitors for the match type.
     *
     * @param  int  $minimumRequired  Minimum number of competitors required
     * @param  int  $currentCount  Current number of competitors assigned
     * @param  string|null  $matchType  Optional match type for specific error messaging
     */
    public static function forMatch(int $minimumRequired, int $currentCount, ?string $matchType = null): static
    {
        $context = $matchType ? " {$matchType}" : '';

        return new self(sprintf(
            'Match%s has %d competitors but requires at least %d competitors.',
            $context,
            $currentCount,
            $minimumRequired
        ));
    }

    /**
     * Championship match does not have sufficient challengers.
     *
     * @param  int  $minimumChallengerCount  Minimum number of challengers required
     * @param  int  $currentChallengerCount  Current number of challengers
     * @param  string|null  $championshipName  Optional championship name for context
     */
    public static function forChampionshipMatch(int $minimumChallengerCount, int $currentChallengerCount, ?string $championshipName = null): static
    {
        $context = $championshipName ? " for {$championshipName}" : '';

        return new self(sprintf(
            'Championship match%s has %d challengers but requires at least %d challengers.',
            $context,
            $currentChallengerCount,
            $minimumChallengerCount
        ));
    }
}
