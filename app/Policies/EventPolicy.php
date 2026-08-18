<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Events\Event;
use App\Models\Users\User;

/**
 * Simplified EventPolicy using global Gate hook.
 *
 * All repetitive administrator checks are handled by the global Gate hook.
 * Business validation is handled in Actions using custom exceptions.
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Event $event): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Event $event): bool
    {
        return false;
    }

    public function delete(User $user, Event $event): bool
    {
        return false;
    }

    public function restore(User $user, Event $event): bool
    {
        return false;
    }
}
