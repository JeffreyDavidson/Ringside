<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Data\Users\UserData;
use App\Models\Users\User;

class UpdateAction
{
    public function handle(User $user, UserData $data): User
    {
        $attributes = [
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'role' => $data->role,
        ];

        if ($data->password !== null) {
            $attributes['password'] = $data->password;
        }

        $user->update($attributes);

        return $user;
    }
}
