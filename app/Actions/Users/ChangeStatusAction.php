<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

final readonly class ChangeStatusAction
{
    public function __construct(private EnsureAnotherActiveAdministratorAction $ensureAnotherActiveAdministrator) {}

    public function handle(User $user, UserStatus $status): User
    {
        return DB::transaction(function () use ($user, $status): User {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->status !== $status) {
                if ($status !== UserStatus::Active && $lockedUser->role->isAdministrator()) {
                    $this->ensureAnotherActiveAdministrator->handle($lockedUser);
                }

                $lockedUser->status = $status;
                $lockedUser->save();
            }

            return $lockedUser;
        });
    }
}
