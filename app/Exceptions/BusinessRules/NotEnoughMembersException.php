<?php

declare(strict_types=1);

namespace App\Exceptions\BusinessRules;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Model;

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
final class NotEnoughMembersException extends BaseBusinessException
{
    /**
     * Tag team does not have the required number of wrestlers for operation.
     *
     * @param  TagTeam  $tagTeam  The tag team with insufficient wrestlers
     * @param  int  $currentCount  Current number of wrestlers in the team
     */
    public static function forTagTeam(TagTeam $tagTeam, int $currentCount): static
    {
        $context = self::formatModelContext($tagTeam);
        $requiredCount = TagTeam::NUMBER_OF_WRESTLERS_ON_TEAM;

        return new self(
            "{$context} has {$currentCount} wrestlers but requires exactly {$requiredCount} wrestlers to operate."
        );
    }

    /**
     * Stable does not have the minimum required number of members for operation.
     *
     * @param  Model  $stable  The stable with insufficient members
     * @param  int  $minimumRequired  Minimum number of members required
     * @param  int  $currentCount  Current number of active members
     */
    public static function forStable(Model $stable, int $minimumRequired, int $currentCount): static
    {
        $context = self::formatModelContext($stable);

        return new static(
            "{$context} has {$currentCount} members but requires at least {$minimumRequired} members to operate."
        );
    }

    /**
     * Match does not have sufficient competitors for the match type.
     *
     * @param  Model  $match  The match with insufficient competitors
     * @param  int  $minimumRequired  Minimum number of competitors required
     * @param  int  $currentCount  Current number of competitors assigned
     */
    public static function forMatch(Model $match, int $minimumRequired, int $currentCount): static
    {
        $context = self::formatModelContext($match);

        return new static(
            "{$context} has {$currentCount} competitors but requires at least {$minimumRequired} competitors."
        );
    }

    /**
     * Championship match does not have sufficient challengers.
     *
     * @param  Model  $championship  The championship with insufficient challengers
     * @param  int  $minimumChallengerCount  Minimum number of challengers required
     * @param  int  $currentChallengerCount  Current number of challengers
     */
    public static function forChampionshipMatch(Model $championship, int $minimumChallengerCount, int $currentChallengerCount): static
    {
        $context = self::formatModelContext($championship);

        return new static(
            "Championship match for {$context} has {$currentChallengerCount} challengers but requires at least {$minimumChallengerCount} challengers."
        );
    }
}
