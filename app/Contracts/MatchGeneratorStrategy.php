<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchType;

/**
 * Interface for match generation strategies.
 *
 * Provides a contract for implementing different match generation approaches
 * based on match type, competitor requirements, and championship implications.
 */
interface MatchGeneratorStrategy
{
    /**
     * Generate a complete event match based on the provided configuration.
     *
     * @param array<string, mixed> $config Match generation configuration
     * @return EventMatch The generated match with all relationships
     */
    public function generateMatch(array $config): EventMatch;

    /**
     * Check if this strategy supports the specified match type.
     */
    public function supportsMatchType(MatchType $matchType): bool;

    /**
     * Validate that the provided competitors are valid for the match type.
     *
     * @param array<mixed> $competitors Array of competitor models or types
     */
    public function validateCompetitors(MatchType $matchType, array $competitors): bool;

    /**
     * Get the minimum number of competitors required for this strategy.
     */
    public function getMinimumCompetitors(MatchType $matchType): int;

    /**
     * Get the maximum number of competitors allowed for this strategy.
     */
    public function getMaximumCompetitors(MatchType $matchType): int;

    /**
     * Determine if this strategy can handle title matches.
     */
    public function supportsTitleMatches(): bool;

    /**
     * Validate title compatibility with competitors.
     *
     * @param array<mixed> $titles Array of title models
     * @param array<mixed> $competitors Array of competitor models
     */
    public function validateTitleCompatibility(array $titles, array $competitors): bool;
}