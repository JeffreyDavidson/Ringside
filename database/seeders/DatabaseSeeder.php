<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $dateToStart = Carbon::now()->subYears(5);

        $this->call(MatchTypesTableSeeder::class);
        $this->call(MatchDecisionsTableSeeder::class);
        $this->call(VenuesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(TitlesTableSeeder::class, false, $dateToStart->copy());
        $this->call(WrestlersTableSeeder::class, false, $dateToStart->copy());
        $this->call(TagTeamsTableSeeder::class, false, $dateToStart->copy());
        $this->call(ManagersTableSeeder::class, false, $dateToStart->copy());
        $this->call(RefereesTableSeeder::class, false, $dateToStart->copy());
        $this->call(StablesTableSeeder::class, false, $dateToStart->copy());
        $this->call(EventsTableSeeder::class);
    }

    public function call($class, $silent = false, $extra = null)
    {
        $this->resolve($class)->run($extra);

        if (isset($this->command)) {
            $this->command->getOutput()->writeln("<info>Seeded:</info> {$class}");
        }
    }
}
