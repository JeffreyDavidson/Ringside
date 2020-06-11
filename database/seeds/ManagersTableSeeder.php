<?php

use Illuminate\Database\Seeder;
use Tests\Factories\ManagerFactory;
use Tests\Factories\EmploymentFactory;

class ManagersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($w = 1; $w <= 50; $w++) {
            ManagerFactory::new()
                ->employed(
                    EmploymentFactory::new()->started(now()->subYears(1))
                )
                ->available()
                ->create(['first_name' => 'Manager', 'last_name' => $w]);
        }

        $eNum = 51;
        for ($i = 1; $i <= 12; $i++) {
            for ($j = 1; $j <= 5; $j++) {
                ManagerFactory::new()
                    ->pendingEmployment(
                        EmploymentFactory::new()->started(now()->subYears(1)->addMonth($i))
                    )
                    ->create(['first_name' => 'Manager', 'last_name' => $eNum]);

                $eNum ++;
            }
        }

        for ($i = 1; $i <= 3; $i++) {
            for ($j = 1; $j <= 5; $j++) {
                ManagerFactory::new()
                    ->pendingEmployment(
                        EmploymentFactory::new()->started(now()->addMonth($i))
                    )
                    ->create(['first_name' => 'Manager', 'last_name' => $eNum]);

                $eNum ++;
            }
        }

        for ($i = 1; $i <= 10; $i++) {
            ManagerFactory::new()
                ->unemployed()
                ->create(['first_name' => 'Manager', 'last_name' => $eNum]);

            $eNum ++;
        }
    }
}
