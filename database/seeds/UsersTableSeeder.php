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
        factory(User::class)->firstOrCreate(['first_name' => 'Admin', 'last_name' => 'User', 'email' => 'admin@example.com']);
    }
}
