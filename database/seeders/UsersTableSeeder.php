<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect(Role::cases())->each(function ($role, $key): void {
            for ($i = 0; $i <= 1; $i++) {
                User::query()->firstOrNew([
                    'first_name' => ($i ? 'Second ' : '').ucwords($role->value),
                    'last_name' => 'User',
                    'email' => $role->value.($i ? '2' : '').'@example.com',
                    'password' => 'password',
                    'role' => $role->value
                ]);
            }
        });
    }
}
