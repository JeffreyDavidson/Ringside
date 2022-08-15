<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activation;
use App\Models\Retirement;
use App\Models\Title;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TitlesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param  mixed|null  $dateToStart
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
        $minYears = (int) (ceil($diffInYears * .25));
        $maxYears = (int) (floor($diffInYears * .75));
        $randomNumberOfYearsActivated = rand($minYears, $maxYears);

        /**
         * We need to create 5 titles at this time X years ago but since by
         * the time we reach the current date these titles should be
         * released so we need to make them released and figure out
         * their started and ended activation date.
         */
        for ($j = $eNum; $j <= 5; $j++) {
            $start = $startDate;
            $end = $start->copy()->addYears($randomNumberOfYearsActivated)->addMonths(rand(1, 11));

            $activation = Activation::factory()->started($start);

            Title::factory()
                ->active($activation)
                ->create(['name' => 'Title '.$eNum.' Title']);

            $eNum++;
        }

        /**
         * We need to create 1 title at this time X years ago but since by
         * the time we reach the current date these titles should be
         * released so we need to make them released and figure out
         * their started and ended activation date.
         */
        $start = $startDate;
        $end = $start->copy()->addYears($randomNumberOfYearsActivated)->addMonths(rand(1, 11));

        $activation = Activation::factory()->started($start);

        if ($end->lessThan($now)) {
            $activation = $activation->ended($end);
        }

        Title::factory()
            ->active($activation)
            ->create(['name' => 'Title '.$eNum.' Title']);

        $eNum++;

        /**
         * We need to create 2 titles that have been retired. We need to
         * make sure that their activation end date is the same as their
         * start of their retirement date.
         */
        for ($i = 1; $i <= 2; $i++) {
            $start = $startDate->copy();
            $end = $start->copy()->addYears($randomNumberOfYearsActivated)->addMonth(rand(1, 11));

            $activation = Activation::factory()->started($start)->ended($end);
            $retirement = Retirement::factory()->started($end);
            Title::factory()
                ->retired($activation, $retirement)
                ->create(['name' => 'Title '.$eNum.' Title']);

            $eNum++;
        }

        /*
         * We need to create 1 title for the the future activation.
         */
        Title::factory()
            ->withFutureActivation()
            ->create(['name' => 'Title '.$eNum.' Title']);

        $eNum++;

        /**
         * We need to create 1 title that does not have an activation date.
         * This title should be marked as being unactivated.
         */
        Title::factory()
            ->unactivated()
            ->create(['name' => 'Title '.$eNum.' Title']);
    }
}
