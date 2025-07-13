<?php

declare(strict_types=1);

namespace App\Enums\Stables;

/**
 * Enumeration for stable membership actions.
 *
 * Defines the types of operations that can be performed on stable memberships.
 */
enum StableMembershipAction: string
{
    case ADD = 'add';
    case REMOVE = 'remove';

    /**
     * Get a human-readable label for this action.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ADD => 'Add',
            self::REMOVE => 'Remove',
        };
    }

    /**
     * Get the past tense form of this action.
     */
    public function getPastTense(): string
    {
        return match ($this) {
            self::ADD => 'added',
            self::REMOVE => 'removed',
        };
    }
}
