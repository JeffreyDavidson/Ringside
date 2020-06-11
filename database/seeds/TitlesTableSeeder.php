<?php

use Illuminate\Database\Seeder;
use Tests\Factories\TitleFactory;

class TitlesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($w = 0; $w < 5; $w++) {
            TitleFactory::new()
                ->active()
                ->create();
        }

        TitleFactory::new()
            ->futureActivation()
            ->create();

        TitleFactory::new()
            ->retired()
            ->create();

        TitleFactory::new()
            ->inactive()
            ->create();

        TitleFactory::new()
            ->unactivated()
            ->create();
    }
}
