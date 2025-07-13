<?php

declare(strict_types=1);

namespace App\Exceptions\BusinessRules;

use App\Exceptions\BaseBusinessException;

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
class WrestlerNotOnCurrentTagTeamException extends BaseBusinessException
{
    /**
     * Wrestler is not currently a member of the specified tag team.
     *
     * @param  string|null  $wrestlerName  Optional wrestler name for specific error messaging
     * @param  string|null  $tagTeamName  Optional tag team name for context
     */
    public static function notCurrentMember(?string $wrestlerName = null, ?string $tagTeamName = null): static
    {
        $wrestler = $wrestlerName ? "Wrestler '{$wrestlerName}'" : 'Wrestler';
        $team = $tagTeamName ? " tag team '{$tagTeamName}'" : ' tag team';

        /** @var static */
        return new self("{$wrestler} is not currently a member of the{$team}.");
    }

    /**
     * Wrestler was previously a member but is no longer active on the tag team.
     *
     * @param  string|null  $wrestlerName  Optional wrestler name for specific error messaging
     * @param  string|null  $tagTeamName  Optional tag team name for context
     */
    public static function formerMember(?string $wrestlerName = null, ?string $tagTeamName = null): static
    {
        $wrestler = $wrestlerName ? "Wrestler '{$wrestlerName}'" : 'Wrestler';
        $team = $tagTeamName ? " tag team '{$tagTeamName}'" : ' tag team';

        return new self("{$wrestler} is a former member but not currently active on the{$team}.");
    }

    /**
     * Wrestler cannot be modified for the tag team due to membership requirements.
     *
     * @param  string  $operation  Description of the attempted operation
     * @param  string|null  $wrestlerName  Optional wrestler name for specific error messaging
     * @param  string|null  $tagTeamName  Optional tag team name for context
     */
    public static function cannotPerformOperation(string $operation, ?string $wrestlerName = null, ?string $tagTeamName = null): static
    {
        $wrestler = $wrestlerName ? "wrestler '{$wrestlerName}'" : 'wrestler';
        $team = $tagTeamName ? " tag team '{$tagTeamName}'" : ' tag team';

        return new self("Cannot {$operation} {$wrestler} - not a current member of the{$team}.");
    }
}
