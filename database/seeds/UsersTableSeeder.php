<?php

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userData = [];
        collect(\App\Enums\Role::toArray())->each(function ($value, $key) use (&$userData): void {
            for ($i = 0; $i <= 1; $i++) {
                $user             = new User();
                $user->first_name = ($i ? 'Second ' : '') . ucwords($value);
                $user->last_name  = 'User';
                $user->email      = $value . ($i ? '2' : '') . '@example.com';
                $user->password   = 'password';
                $user->role = new \App\Enums\Role($value);
                $user->save();
                $user->refresh();
                $userData[] = [
                    'role'     => $value,
                    'name'     => $user->first_name . ' ' . $user->last_name,
                    'email'    => $user->email,
                    'password' => 'password',
                ];
            }
        });
        $this->command->table(['Role', 'Name', 'Email', 'Password'], $userData);
    }
}
