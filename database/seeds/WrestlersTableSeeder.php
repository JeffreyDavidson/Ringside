<?php

use App\Models\Wrestler;
use Illuminate\Database\Seeder;

class WrestlersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Wrestler::class, 50)->create(['hired_at' => now()->subYears(1)]);

        for ($i = 1; $i <= 12; $i++) {
            factory(Wrestler::class, 5)->create([
                'hired_at' => now()->subYear(1)->addMonth($i)
            ]);
        }
    }
}
