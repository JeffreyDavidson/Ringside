<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Title;
use Illuminate\Auth\Access\HandlesAuthorization;

class TitlePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create a title.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a title.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a title.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Title  $title
     * @return bool
     */
    public function delete(User $user, Title $title)
    {
        if ($title->trashed()) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a title.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Title  $title
     * @return bool
     */
    public function restore(User $user, Title $title)
    {
        if (!$title->trashed()) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a title.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function retire(User $user, Title $title)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a title.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function unretire(User $user, Title $title)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can activate a title.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Title  $title
     * @return bool
     */
    public function activate(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can deactivate a title.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Title  $title
     * @return bool
     */
    public function deactivate(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of titles.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a title.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function view(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }
}
