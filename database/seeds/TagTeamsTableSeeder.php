<?php

use Illuminate\Database\Seeder;
use Tests\Factories\TagTeamFactory;
use Tests\Factories\EmploymentFactory;

class TagTeamsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $eNum = 1;

        for ($w = 1; $w <= 20; $w++) {
            TagTeamFactory::new()
                ->bookable(
                    EmploymentFactory::new()->started(now()->subYears(1))
                )
                ->create(['name' => 'Tag Team '.$w]);

            $eNum ++;
        }

        for ($i = 1; $i <= 12; $i++) {
            for ($j = 1; $j <= 2; $j++) {
                TagTeamFactory::new()
                    ->pendingEmployment(
                        EmploymentFactory::new()->started(now()->subYears(1)->addMonth($i))
                    )
                    ->create(['name' => 'Tag Team '.$eNum]);

                $eNum ++;
            }
        }

        for ($i = 1; $i <= 3; $i++) {
            for ($j = 1; $j <= 2; $j++) {
                TagTeamFactory::new()
                    ->pendingEmployment(
                        EmploymentFactory::new()->started(now()->addMonth($i))
                    )
                    ->create(['name' => 'Tag Team '.$eNum]);

                $eNum ++;
            }
        }

        for ($i = 1; $i <= 10; $i++) {
            TagTeamFactory::new()
                ->unemployed()
                ->create(['name' => 'Tag Team '.$eNum]);

            $eNum ++;
        }
    }
}
