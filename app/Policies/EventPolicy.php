<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Event;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create an event.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update an event.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete an event.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Event  $event
     * @return bool
     */
    public function delete(User $user, Event $event)
    {
        return $user->isAdministrator() && $event->isScheduled();
    }

    /**
     * Determine whether the user can restore a deleted event.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Event  $event
     * @return bool
     */
    public function restore(User $user, Event $event)
    {
        return $user->isAdministrator() && $event->deleted_at !== null;
    }

    /**
     * Determine whether the user can view a list of events.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can archive an event.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Event  $event
     * @return bool
     */
    public function archive(User $user, Event $event)
    {
        return $user->isAdministrator() && $event->isPast() && ! $event->isArchived();
    }
}
