<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Actions\Concerns\ManagesDates;
use App\Repositories\EventMatchRepository;

/**
 * Base class for all event match actions.
 *
 * Provides common functionality for actions that operate on event matches:
 * - Centralized repository access for consistent data operations
 * - Foundation for match-related business operations (adding competitors, referees, titles)
 * - Standardized repository access patterns
 * - Date handling utilities for match scheduling
 *
 * MATCH MANAGEMENT:
 * - Handles wrestler assignments with proper side allocation
 * - Manages tag team competitor assignments
 * - Coordinates referee assignments for match officiating
 * - Manages title assignments for championship matches
 * - Ensures proper match structure and competition balance
 *
 * BUSINESS RULES:
 * - Matches require proper competitor distribution across sides
 * - Championship matches must have active titles
 * - Referees must be available and qualified for the match type
 * - Competitors must be available and not conflicted for the event date
 */
abstract class BaseEventMatchAction
{
    use ManagesDates;

    /**
     * Create a new base event match action instance.
     */
    public function __construct(protected EventMatchRepository $eventMatchRepository) {}

    /**
     * Validate match competitors for proper side distribution.
     *
     * @param  array<int, array<string, mixed>>  $competitors  Competitors organized by side
     * @return bool True if competitor distribution is valid for the match type
     */
    protected function validateCompetitorDistribution(array $competitors): bool
    {
        // Ensure we have at least 2 sides with competitors
        $sidesWithCompetitors = 0;

        foreach ($competitors as $sideCompetitors) {
            $hasWrestlers = ! empty($sideCompetitors['wrestlers'] ?? []);
            $hasTagTeams = ! empty($sideCompetitors['tag_teams'] ?? []);

            if ($hasWrestlers || $hasTagTeams) {
                $sidesWithCompetitors++;
            }
        }

        return $sidesWithCompetitors >= 2;
    }

    /**
     * Get total competitor count across all sides.
     *
     * @param  array<int, array<string, mixed>>  $competitors  Competitors organized by side
     * @return int Total number of individual competitors
     */
    protected function getTotalCompetitorCount(array $competitors): int
    {
        $total = 0;

        foreach ($competitors as $sideCompetitors) {
            $total += count($sideCompetitors['wrestlers'] ?? []);
            $total += count($sideCompetitors['tag_teams'] ?? []) * 2; // Tag teams count as 2 competitors
        }

        return $total;
    }
}
