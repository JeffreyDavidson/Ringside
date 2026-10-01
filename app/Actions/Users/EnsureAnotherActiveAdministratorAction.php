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
     * Call inside the caller's transaction. Locks every active administrator row so concurrent
     * changes are serialized, then rejects when the given user is the only one left.
     */
    public function handle(User $lockedUser): void
    {
        $activeAdministratorIds = User::query()
            ->where('role', Role::Administrator)
            ->where('status', UserStatus::Active)
            ->lockForUpdate()
            ->pluck('id');

        if (! $activeAdministratorIds->contains($lockedUser->getKey())) {
            return;
        }

        if ($activeAdministratorIds->count() === 1) {
            throw CannotRemoveLastAdministratorException::lastActiveAdministrator();
        }
    }
}
