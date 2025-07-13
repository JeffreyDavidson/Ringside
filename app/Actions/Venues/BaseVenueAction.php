<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Repositories\VenueRepository;

/**
 * Base class for all venue actions.
 *
 * Provides common functionality for actions that operate on venues:
 * - Centralized repository access for consistent data operations
 * - Foundation for venue-related business operations
 * - Ensures dependency injection consistency across venue actions
 */
abstract class BaseVenueAction
{
    /**
     * Create a new base venue action instance.
     */
    public function __construct(protected VenueRepository $venueRepository) {}
}
