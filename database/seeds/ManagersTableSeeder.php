<?php

use App\Models\Manager;
use Illuminate\Database\Seeder;

class ManagersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($w = 1; $w <= 20; $w++) {
            factory(Manager::class)->create([
                'first_name' => 'Manager',
                'last_name' => $w,
            ])->employments()->create([
                'started_at' => now()->subYears(1)
            ]);
        }

        $eNum = 21;
        for ($i = 1; $i <= 15; $i++) {
            for ($j = 1; $j <= 2; $j++) {
                factory(Manager::class)->create([
                    'first_name' => 'Manager',
                    'last_name' => $eNum,
                ])->employments()->create([
                    'started_at' => now()->subYear(1)->addMonth($i)
                ]);
                $eNum ++;
            }
        }
    }
}
