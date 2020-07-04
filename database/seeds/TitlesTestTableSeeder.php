<?php

use Illuminate\Database\Seeder;
use Tests\Factories\TitleFactory;

class TitlesTestTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TitleFactory::new()->active()->times(3)->create();
        TitleFactory::new()->futureActivation()->times(3)->create();
        TitleFactory::new()->unactivated()->times(3)->create();
        TitleFactory::new()->inactive()->times(3)->create();
        TitleFactory::new()->retired()->times(3)->create();
    }
}
