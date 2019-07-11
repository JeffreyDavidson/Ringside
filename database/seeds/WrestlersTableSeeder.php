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
        for ($w = 1; $w <= 50; $w++) {
            factory(Wrestler::class)->create([
                'name' => 'Wrestler '.$w,
                'hired_at' => now()->subYears(1)
            ]);
        }

        for ($i = 1; $i <= 12; $i++) {
            for ($j = 51; $j <= 55; $j++) {
                factory(Wrestler::class)->create([
                    'name' => 'Wrestler '. $j,
                    'hired_at' => now()->subYear(1)->addMonth($i)
                ]);
            }
        }
    }
}
