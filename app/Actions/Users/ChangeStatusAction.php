<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

final class ChangeStatusAction
{
    public function handle(User $user, UserStatus $status): User
    {
        return DB::transaction(function () use ($user, $status): User {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->status !== $status) {
                $lockedUser->status = $status;
                $lockedUser->save();
            }

            return $lockedUser;
        });
    }
}
