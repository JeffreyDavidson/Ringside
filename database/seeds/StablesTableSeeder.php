<?php

use App\Models\Stable;
use Illuminate\Database\Seeder;

class StablesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($w = 1; $w <= 10; $w++) {
            factory(Stable::class)->create([
                'name' => 'Stable '.$w,
            ])->employments()->create([
                'started_at' => now()->subYears(1)
            ]);
        }

        $eNum = 11;
        for ($i = 1; $i <= 15; $i++) {
            for ($j = 1; $j <= 2; $j++) {
                factory(Stable::class)->create([
                    'name' => 'Stable '.$eNum,
                ])->employments()->create([
                    'started_at' => now()->subYear(1)->addMonth($i)
                ]);
                $eNum ++;
            }
        }
    }
}
