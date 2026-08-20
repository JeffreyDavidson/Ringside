<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Data\Users\UserData;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function handle(User $user, UserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $attributes = [
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'email' => $data->email,
                'role' => $data->role,
            ];

            if ($data->password !== null) {
                $attributes['password'] = $data->password;
            }

            $lockedUser->update($attributes);

            return $lockedUser;
        });
    }
}
