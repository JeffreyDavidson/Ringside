<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Tests\Factories\ActivationFactory;
use Tests\Factories\RetirementFactory;
use Tests\Factories\StableFactory;

class StablesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param mixed|null $dateToStart
     *
     * @return void
     */
    public function run($dateToStart = null)
    {
        $eNum = 1;
        $now = Carbon::now();

        if (null === $dateToStart) {
            $dateToStart = Carbon::now()->subYears(5);
        }

        $startDate = $dateToStart;
        $diffInYears = $startDate->diffInYears(now());
        $minYears = ceil($diffInYears * .25);
        $maxYears = floor($diffInYears * .75);
        $randomNumberOfYearsEmployed = rand($minYears, $maxYears);

        /**
         * We need to create 3 stables at this time X years ago but since by
         * the time we reach the current date these stables should be
         * inactive so we need to make them inactive and figure out
         * their started and ended activation date.
         */
        for ($j = $eNum; $j <= 3; $j++) {
            $start = $startDate;
            $end = $start->copy()->addYears($randomNumberOfYearsEmployed)->addMonths(rand(1, 11));

            $activation = ActivationFactory::new()->started($start);

            if ($end->lessThan($now)) {
                $activation = $activation->ended($end);
            }

            StableFactory::new()
                ->inactive($activation)
                ->create(['name' => 'Stable '.$eNum]);

            $eNum++;
        }

        /**
         * We need to create 2 stables that have been retired. We need to
         * make sure that their activation end date is the same as their
         * start of their retirement date.
         */
        for ($i = 1; $i <= 2; $i++) {
            $start = $startDate->copy();
            $end = $start->copy()->addYears($randomNumberOfYearsEmployed)->addMonth(rand(1, 11));

            $activation = ActivationFactory::new()->started($start)->ended($end);
            $retirement = RetirementFactory::new()->started($end);
            StableFactory::new()
                ->retired($activation, $retirement)
                ->create(['name' => 'Stable '.$eNum]);

            $eNum++;
        }

        /**
         * We need to create 1 stable at this time x years ago for each
         * 6 months but since by the time we reach the current
         * date these stables should be inactive so we need to
         * make them inactive and figure out their started
         * and ended activation date.
         */
        while ($startDate->lessThan($now)) {
            for ($i = 0; $i < 1; $i++) {
                $start = $startDate->copy()->addMonths(rand(6, 11));
                $end = $start->copy()->addMonth(rand(4, 8));

                $activation = ActivationFactory::new()->started($start);

                if ($end->lessThan($now)) {
                    $activation = $activation->ended($end);
                }

                StableFactory::new()
                    ->inactive($activation)
                    ->create(['name' => 'Stable '.$eNum]);

                $eNum++;
            }

            $startDate->addMonth();
        }

        /**
         * We need to create 1 stables for the next 3 months and
         * should be pending activation and should NOT
         * have an ended activation date.
         */
        for ($j = 1; $j <= 1; $j++) {
            $start = $now->copy()->addMonths(3);

            $activation = ActivationFactory::new()->started($start);

            StableFactory::new()
                ->pendingActivation($activation)
                ->create(['name' => 'Stable '.$eNum]);

            $eNum++;
        }

        /**
         * We need to create 1 stable that does not have an activation date.
         * This stables should be marked as being unactivated.
         */
        for ($i = 1; $i <= 3; $i++) {
            StableFactory::new()
                ->unactivated()
                ->create(['name' => 'Stable '.$eNum]);

            $eNum++;
        }
    }
}
