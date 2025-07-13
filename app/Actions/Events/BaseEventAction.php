<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Actions\Concerns\ManagesDates;
use App\Repositories\EventRepository;

/**
 * Base class for all event actions.
 *
 * Provides common functionality for actions that operate on events:
 * - Centralized repository access for consistent data operations
 * - Foundation for event-related business operations
 * - Ensures dependency injection consistency across event actions
 * - Standardized date handling for event operations
 *
 * EVENT LIFECYCLE STATES:
 * 1. Created (draft) - Event exists but may not have all details finalized
 * 2. Scheduled - Event has date, venue, and basic information set
 * 3. Live - Event is currently happening
 * 4. Completed - Event has finished, matches concluded
 * 5. Cancelled - Event was cancelled before occurring
 * 6. Deleted - Soft deleted, can be restored
 *
 * Note: Events focus on scheduling and match management, not employment states
 */
abstract class BaseEventAction
{
    use ManagesDates;

    /**
     * Create a new base event action instance.
     */
    public function __construct(protected EventRepository $eventRepository) {}
}
