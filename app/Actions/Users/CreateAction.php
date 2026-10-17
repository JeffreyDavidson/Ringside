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

        $user = User::query()->make([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'password' => $data->password,
        ]);
        $user->role = $data->role;
        $user->save();

        return $user;
    }
}
