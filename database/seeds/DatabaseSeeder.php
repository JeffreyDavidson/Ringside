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
        $this->call(VenuesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(TitlesTableSeeder::class);
        $this->call(WrestlersTableSeeder::class);
        $this->call(TagTeamsTableSeeder::class);
        $this->call(ManagersTableSeeder::class);
        $this->call(RefereesTableSeeder::class);
        $this->call(StablesTableSeeder::class);
        $this->call(EventsTableSeeder::class);
    }
}
