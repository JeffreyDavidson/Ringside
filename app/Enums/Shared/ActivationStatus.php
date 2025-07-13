<?php

declare(strict_types=1);

namespace App\Enums\Shared;

/**
 * Common activation status values used across multiple entity types.
 *
 * This enum provides standardized status values that are shared between
 * different entity types (Stables, Titles) for consistent status management.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions need consistent status tracking across different
 * entity types. This enum provides the common statuses used by multiple
 * entity types to ensure consistency in status management.
 *
 * @example
 * ```php
 * // Used by Stables
 * $stable->status = ActivationStatus::Active;
 *
 * // Used by Titles
 * $title->status = ActivationStatus::Inactive;
 * ```
 */
enum ActivationStatus: string
{
    /**
     * Entity has never been activated and has no scheduled activation.
     */
    case Unactivated = 'unactivated';

    /**
     * Entity has a scheduled future activation but is not currently active.
     */
    case FutureActivation = 'future_activation';

    /**
     * Entity is currently active and available for use.
     */
    case Active = 'active';

    /**
     * Entity is currently inactive but can be reactivated.
     */
    case Inactive = 'inactive';

    /**
     * Entity has been permanently retired from use.
     */
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::FutureActivation => 'Awaiting Activation',
            self::Inactive => 'Inactive',
            self::Retired => 'Retired',
            self::Unactivated => 'Unactivated',
        };
    }
}
