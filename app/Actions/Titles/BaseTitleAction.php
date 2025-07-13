<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Actions\Concerns\ManagesDates;
use App\Repositories\TitleRepository;

/**
 * Base class for all title actions.
 *
 * Provides common functionality for actions that operate on titles:
 * - Centralized repository access through dependency injection
 * - Status transition management capabilities for debut, pull, retirement, etc.
 * - Foundation for title-related business operations
 * - Standardized repository access patterns
 *
 * TITLE LIFECYCLE STATES:
 * 1. Created (not debuted) - Title exists but not available for competition
 * 2. Active (debuted) - Available for championship matches and defenses
 * 3. Inactive (pulled) - Temporarily unavailable, can be reinstated
 * 4. Retired - Permanently unavailable, lineage ended
 * 5. Unretired - Retired status reversed, available for debut/reinstatement
 * 6. Deleted - Soft deleted, can be restored
 *
 * Note: Titles only use status management (debut/pull/retire), not employment/suspension/injury
 */
abstract class BaseTitleAction
{
    use ManagesDates;

    /**
     * Create a new base title action instance.
     */
    public function __construct(protected TitleRepository $titleRepository) {}
}
