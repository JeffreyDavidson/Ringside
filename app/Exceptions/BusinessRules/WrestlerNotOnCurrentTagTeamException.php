<?php

declare(strict_types=1);

namespace App\Exceptions\BusinessRules;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;

/**
 * Exception thrown when a wrestler is not part of the current tag team for an operation.
 *
 * This exception handles scenarios where tag team operations require specific wrestler
 * membership that is not currently met.
 *
 * BUSINESS CONTEXT:
 * Tag team operations often require validation that specific wrestlers are current
 * active members of the tag team. This ensures proper team composition and prevents
 * unauthorized modifications to team rosters.
 *
 * COMMON SCENARIOS:
 * - Attempting to remove a wrestler who is not currently on the tag team
 * - Trying to perform team-specific actions with non-member wrestlers
 * - Roster modifications targeting wrestlers outside the current team lineup
 * - Championship defenses with incorrect team composition
 *
 * BUSINESS IMPACT:
 * - Maintains tag team roster integrity and member accountability
 * - Protects championship lineages and title defense legitimacy
 * - Ensures proper storyline continuity and character relationships
 * - Prevents unauthorized team modifications that could affect contracts
 */
final class WrestlerNotOnCurrentTagTeamException extends BaseBusinessException
{
    /**
     * Wrestler is not currently a member of the specified tag team.
     *
     * @param  Model  $wrestler  The wrestler who is not a current member
     * @param  Model  $tagTeam  The tag team the wrestler is not a member of
     */
    public static function notCurrentMember(Model $wrestler, Model $tagTeam): static
    {
        $wrestlerContext = self::formatModelContext($wrestler);
        $tagTeamContext = self::formatModelContext($tagTeam);

        return new self("{$wrestlerContext} is not currently a member of {$tagTeamContext}.");
    }

    /**
     * Wrestler was previously a member but is no longer active on the tag team.
     *
     * @param  Model  $wrestler  The wrestler who is a former member
     * @param  Model  $tagTeam  The tag team the wrestler was previously on
     */
    public static function formerMember(Model $wrestler, Model $tagTeam): static
    {
        $wrestlerContext = self::formatModelContext($wrestler);
        $tagTeamContext = self::formatModelContext($tagTeam);

        return new static("{$wrestlerContext} is a former member but not currently active on {$tagTeamContext}.");
    }

    /**
     * Wrestler cannot be modified for the tag team due to membership requirements.
     *
     * @param  Model  $wrestler  The wrestler who cannot be modified
     * @param  Model  $tagTeam  The tag team for which the operation cannot be performed
     * @param  string  $operation  Description of the attempted operation
     */
    public static function cannotPerformOperation(Model $wrestler, Model $tagTeam, string $operation): static
    {
        $wrestlerContext = self::formatModelContext($wrestler);
        $tagTeamContext = self::formatModelContext($tagTeam);

        return new static("Cannot {$operation} {$wrestlerContext} - not a current member of {$tagTeamContext}.");
    }
}
