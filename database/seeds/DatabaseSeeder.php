<?php

use Carbon\Carbon;
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
        $dateToStart = Carbon::now()->subYears(5);
        // dd($dateToStart);

        // $this->call(MatchTypesTableSeeder::class);
        // $this->call(MatchDecisionsTableSeeder::class);
        // $this->call(VenuesTableSeeder::class);
        // $this->call(UsersTableSeeder::class);
        // $this->call(TitlesTableSeeder::class);
        $this->call(WrestlersTableSeeder::class, $dateToStart);
        // $this->call(TagTeamsTableSeeder::class, $dateToStart);
        // $this->call(ManagersTableSeeder::class, $dateToStart);
        // $this->call(RefereesTableSeeder::class, $dateToStart);
        // $this->call(StablesTableSeeder::class);
        // $this->call(EventsTableSeeder::class);
    }

    public function call($class, $extra = null)
    {
        $this->resolve($class)->run($extra);

        if (isset($this->command)) {
            $this->command->getOutput()->writeln("<info>Seeded:</info> $class");
        }
    }
}
