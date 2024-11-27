<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userData = [];
        collect(Role::cases())->each(function ($role, $key) use (&$userData): void {
            for ($i = 0; $i <= 1; $i++) {
                $user = new User;
                $user->first_name = ($i ? 'Second ' : '').ucwords($role->value);
                $user->last_name = 'User';
                $user->email = $role->value.($i ? '2' : '').'@example.com';
                $user->password = 'password';
                $user->role = $role->value;
                $user->status = UserStatus::Active;
                $user->save();
                $user->refresh();
                $userData[] = [
                    'role' => $role->value,
                    'name' => $user->first_name.' '.$user->last_name,
                    'email' => $user->email,
                    'password' => 'password',
                ];
            }
        });
        $this->command->table(['Role', 'Name', 'Email', 'Password'], $userData);
    }
}
