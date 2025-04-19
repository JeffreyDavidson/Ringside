<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewList(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, User $requestedUser): bool
    {
        if ($user->is($requestedUser)) {
            return true;
        }

        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }
}
