<?php

use Illuminate\Database\Seeder;
use Tests\Factories\StableFactory;

class StablesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $eNum = 1;

        for ($w = 1; $w <= 3; $w++) {
            StableFactory::new()
                ->active()
                ->create(['name' => 'Stable '.$eNum]);

                $eNum ++;
        }

        StableFactory::new()
            ->pendingActivation()
            ->create(['name' => 'Stable '.$eNum]);

        $eNum ++;

        StableFactory::new()
            ->inactive()
            ->create(['name' => 'Stable '.$eNum]);

        $eNum ++;

        StableFactory::new()
            ->retired()
            ->create(['name' => 'Stable '.$eNum]);
    }
}
