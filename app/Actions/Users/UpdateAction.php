<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Data\Users\UserData;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(private readonly EnsureAnotherActiveAdministratorAction $ensureAnotherActiveAdministrator) {}

    public function handle(User $user, UserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $lockedUser = $user->refreshForUpdate();

            if ($lockedUser->role->isAdministrator() && ! $data->role->isAdministrator()) {
                $this->ensureAnotherActiveAdministrator->handle($lockedUser);
            }

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
