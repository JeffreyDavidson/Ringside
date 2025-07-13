<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\ManagesDates;
use App\Repositories\ManagerRepository;

/**
 * Base class for all manager actions.
 *
 * Provides common functionality for actions that operate on managers:
 * - Centralized repository access through dependency injection
 * - Status transition management capabilities for employment, retirement, etc.
 * - Foundation for manager-related business operations
 * - Standardized repository access patterns
 *
 * MANAGER LIFECYCLE STATES:
 * 1. Created (unemployed) - Manager exists but not available for talent management
 * 2. Employed - Available for managing wrestlers, tag teams, and stables
 * 3. Suspended - Employed but temporarily restricted from active management
 * 4. Injured - Employed but unable to manage due to injury
 * 5. Released - Employment ended, no longer with promotion
 * 6. Retired - Career ended, permanently unavailable for management
 * 7. Deleted - Soft deleted, can be restored
 *
 * Note: Managers can be employed, suspended, injured, retired, but cannot be "debuted" like titles
 */
abstract class BaseManagerAction
{
    use ManagesDates;

    /**
     * Create a new base manager action instance.
     */
    public function __construct(protected ManagerRepository $managerRepository) {}
}
