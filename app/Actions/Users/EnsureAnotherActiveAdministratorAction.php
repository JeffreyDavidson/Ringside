<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Exceptions\Users\CannotRemoveLastAdministratorException;
use App\Models\Users\User;

final class EnsureAnotherActiveAdministratorAction
{
    /**
     * Call inside the caller's transaction before locking the user's own row. Locks every active administrator
     * row in ascending id order so concurrent changes are serialized (two administrators removing each other
     * queue instead of deadlocking), then rejects when the given user is the only one left.
     */
    public function handle(User $user): void
    {
        $activeAdministratorIds = User::query()
            ->where('role', Role::Administrator)
            ->where('status', UserStatus::Active)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id');

        if (! $activeAdministratorIds->contains($user->getKey())) {
            return;
        }

        if ($activeAdministratorIds->count() === 1) {
            throw CannotRemoveLastAdministratorException::lastActiveAdministrator();
        }
    }
}
