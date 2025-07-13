<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Actions\Concerns\ManagesDates;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;

/**
 * Base class for all referee actions.
 *
 * Provides common functionality for actions that operate on referees:
 * - Centralized repository access through dependency injection
 * - Status transition management capabilities for employment, retirement, etc.
 * - Foundation for referee-related business operations
 * - Standardized repository access patterns
 */
abstract class BaseRefereeAction
{
    use ManagesDates;

    /**
     * Create a new base referee action instance.
     */
    public function __construct(protected RefereeRepository $refereeRepository) {}
}
