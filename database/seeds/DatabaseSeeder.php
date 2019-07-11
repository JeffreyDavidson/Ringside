<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(MatchTypesTableSeeder::class);
        $this->call(MatchDecisionsTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(WrestlersTableSeeder::class);
    }
}
