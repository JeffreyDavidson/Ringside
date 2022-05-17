<?php

namespace Database\Seeders;

use App\Models\Employment;
use App\Models\Referee;
use App\Models\Retirement;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class RefereesTableSeeder extends Seeder
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
         * We need to create 30 referees at this time X years ago but since by
         * the time we reach the current date these referees should be
         * released so we need to make them released and figure out
         * their started and ended employment date.
         */
        for ($j = $eNum; $j <= 30; $j++) {
            $start = $startDate;
            $end = $start->copy()->addYears($randomNumberOfYearsEmployed)->addMonths(rand(1, 11));

            $employment = Employment::factory()->started($start);

            if ($end->lessThan($now)) {
                $employment = $employment->ended($end);
            }

            Referee::factory()
                ->released($employment)
                ->create(['first_name' => 'Referee', 'last_name' => $eNum]);

            $eNum++;
        }

        /**
         * We need to create 10 referees that have been retired. We need to
         * make sure that their employment end date is the same as their
         * start of their retirement date.
         */
        for ($i = 1; $i <= 10; $i++) {
            $start = $startDate->copy();
            $end = $start->copy()->addYears($randomNumberOfYearsEmployed)->addMonth(rand(1, 11));

            $employment = Employment::factory()->started($start)->ended($end);
            $retirement = Retirement::factory()->started($end);
            Referee::factory()
                ->retired($employment, $retirement)
                ->create(['first_name' => 'Referee', 'last_name' => $eNum]);

            $eNum++;
        }

        /**
         * We need to create 5 referees at this time x years ago for each
         * additional month but since by the time we reach the current
         * date these referees should be released so we need to
         * make them released and figure out their started
         * and ended employment date.
         */
        while ($startDate->lessThan($now)) {
            for ($i = 0; $i < 5; $i++) {
                $start = $startDate->copy()->addDays(rand(1, 25));
                $end = $start->copy()->addMonth(rand(1, 11));

                $employment = Employment::factory()->started($start);

                if ($end->lessThan($now)) {
                    $employment = $employment->ended($end);
                }

                Referee::factory()
                    ->released($employment)
                    ->create(['first_name' => 'Referee', 'last_name' => $eNum]);

                $eNum++;
            }

            $startDate->addMonth();
        }

        /**
         * We need to create 5 referees for the next 3 months and all
         * referees should be Future Employment and should NOT
         * have an ended employment date.
         */
        for ($j = 1; $j <= 5; $j++) {
            $start = $now->copy()->addMonths(3);

            $employment = Employment::factory()->started($start);

            Referee::factory()
                ->withFutureEmployment($employment)
                ->create(['first_name' => 'Referee', 'last_name' => $eNum]);

            $eNum++;
        }

        /**
         * We need to create 5 referees that do not have an employment date.
         * These referees should be marked as being Unemployed.
         */
        for ($i = 1; $i <= 5; $i++) {
            Referee::factory()
                ->unemployed()
                ->create(['first_name' => 'Referee', 'last_name' => $eNum]);

            $eNum++;
        }
    }
}
