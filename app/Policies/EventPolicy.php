<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create an event.
     *
     * @param  \App\Models\User  $user
     *
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update an event.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Event  $event
     *
     * @return bool
     */
    public function update(User $user, Event $event)
    {
        if (! $user->isAdministrator()) {
            return false;
        }

        if (isset($event->date) && $event->date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete an event.
     *
     * @param  \App\Models\User  $user
     *
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted event.
     *
     * @param  \App\Models\User  $user
     *
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of events.
     *
     * @param  \App\Models\User  $user
     *
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view an event.
     *
     * @param  \App\Models\User  $user
     *
     * @return bool
     */
    public function view(User $user)
    {
        return $user->isAdministrator();
    }
}
