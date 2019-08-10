<?php

use App\Models\Title;
use Illuminate\Database\Seeder;

class TitlesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($w = 1; $w <= 5; $w++) {
            factory(Title::class)->create([
                'name' => 'Title '.$w,
                'introduced_at' => now()->subYear(1)
            ]);
        }

        $eNum = 6;
        for ($i = 1; $i <= 12; $i++) {
            for ($j = 1; $j <= 2; $j++) {
                factory(Title::class)->create([
                    'name' => 'Title '. $eNum,
                    'introduced_at' => now()->subYear(1)->addMonths($i)
                ]);
                $eNum ++;
            }
        }
    }
}
