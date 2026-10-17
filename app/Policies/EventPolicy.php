<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Events\Event;
use App\Models\Users\User;

/**
 * Stub: PromotionGate (Gate::before) decides every ability on this model from the user's
 * promotion membership role, so these methods only run when the gate falls through and deny.
 */
class EventPolicy
{
    public function preparePublication(User $user, Event $event): bool
    {
        return false;
    }

    public function publishPublication(User $user, Event $event): bool
    {
        return false;
    }

    public function unpublishPublication(User $user, Event $event): bool
    {
        return false;
    }

    public function uploadPublicationPoster(User $user, Event $event): bool
    {
        return false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Event $event): bool
    {
        return false;
    }

    public function viewProduction(User $user, Event $event): bool
    {
        return false;
    }

    public function updateProduction(User $user, Event $event): bool
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

    public function viewBudget(User $user, Event $event): bool
    {
        return false;
    }

    public function updateBudget(User $user, Event $event): bool
    {
        return false;
    }

    public function manageSponsors(User $user, Event $event): bool
    {
        return false;
    }

    public function restore(User $user, Event $event): bool
    {
        return false;
    }
}
