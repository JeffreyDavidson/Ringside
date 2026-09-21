<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Data\Users\UserData;
use App\Models\Users\User;
use InvalidArgumentException;

class CreateAction
{
    public function handle(UserData $data): User
    {
        if ($data->password === null) {
            throw new InvalidArgumentException('A password is required to create a user.');
        }

        return User::query()->create([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'role' => $data->role,
            'password' => $data->password,
        ]);
    }
}
