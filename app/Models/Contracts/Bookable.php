<?php

declare(strict_types=1);

namespace App\Models\Contracts;

/**
 * Base contract for models that can be booked.
 *
 * This interface provides the minimal contract for bookable entities,
 * containing only the common isBookable() method.
 */
interface Bookable
{
    /**
     * Determine if the entity is eligible to be booked.
     */
    public function isBookable(): bool;
}
