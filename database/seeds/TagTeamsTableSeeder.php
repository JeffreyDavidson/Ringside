<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Tests\Factories\EmploymentFactory;
use Tests\Factories\RetirementFactory;
use Tests\Factories\TagTeamFactory;

class TagTeamsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($dateToStart = null)
    {
        $eNum = 1;
        $now = Carbon::now();

        if (is_null($dateToStart)) {
            $dateToStart = Carbon::now()->subYears(5);
        }

        $startDate = $dateToStart;
        $diffInYears = $startDate->diffInYears(now());
        $minYears = ceil($diffInYears*.25);
        $maxYears = floor($diffInYears*.75);
        $randomNumberOfYearsEmployed = rand($minYears, $maxYears);

        /**
         * We need to create 15 tag teams at this time X years ago but since by
         * the time we reach the current date these tag teams should be
         * released so we need to make them released and figure out
         * their started and ended employment date.
         */
        for ($j = $eNum; $j <= 10; $j++) {
            $start = $startDate;
            $end = $start->copy()->addYears($randomNumberOfYearsEmployed)->addMonths(rand(1, 11));

            $employment = EmploymentFactory::new()->started($start);

            if ($end->lessThan($now)) {
                $employment = $employment->ended($end);
            }

            TagTeamFactory::new()
                ->released($employment)
                ->create(['name' => 'Tag Team '.$eNum]);

            $eNum ++;
        }

        /**
         * We need to create 10 tag teams that have been retired. We need to
         * make sure that their employment end date is the same as their
         * start of their retirement date.
         */
        for ($i = 1; $i <= 10; $i++) {
            $start = $startDate->copy();
            $end = $start->copy()->addYears($randomNumberOfYearsEmployed)->addMonth(rand(1, 11));

            $employment = EmploymentFactory::new()->started($start)->ended($end);
            $retirement = RetirementFactory::new()->started($end);
            TagTeamFactory::new()
                ->retired($employment, $retirement)
                ->create(['name' => 'Tag Team '.$eNum]);

            $eNum ++;
        }

        /**
         * We need to create 5 tag teams at this time x years ago for each
         * additional month but since by the time we reach the current
         * date these tag teams should be released so we need to
         * make them released and figure out their started
         * and ended employment date.
         */
        while ($startDate->lessThan($now)) {
            for ($i = 0; $i < 1; $i++) {
                $start = $startDate->copy()->addDays(rand(1, 25));
                $end = $start->copy()->addMonth(rand(1, 11));

                $employment = EmploymentFactory::new()->started($start);

                if ($end->lessThan($now)) {
                    $employment = $employment->ended($end);
                }

                TagTeamFactory::new()
                    ->released($employment)
                    ->create(['name' => 'Tag Team '.$eNum]);

                $eNum++;
            }

            $startDate->addMonth();
        }

        /**
         * We need to create 3 tag teams for the next 3 months and all
         * tag teams should be Pending Employment and should NOT
         * have an ended employment date.
         */
        for ($j = 1; $j <= 3; $j++) {
            $start = $now->copy()->addMonths(3);

            $employment = EmploymentFactory::new()->started($start);

            TagTeamFactory::new()
                ->pendingEmployment($employment)
                ->create(['name' => 'Tag Team '.$eNum]);

            $eNum ++;
        }

        /**
         * We need to create 3 tag teams that do not have an employment date.
         * These tag teams should be marked as being Unemployed.
         */
        for ($i = 1; $i <= 3; $i++) {
            TagTeamFactory::new()
                ->unemployed()
                ->create(['name' => 'Tag Team '.$eNum]);

            $eNum ++;
        }
    }
}
