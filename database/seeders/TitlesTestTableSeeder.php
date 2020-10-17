<?php

namespace Database\Seeders;

use App\Models\Title;
use Illuminate\Database\Seeder;

class TitlesTestTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Title::factory()->active()->times(3)->create();
        Title::factory()->futureActivation()->times(3)->create();
        Title::factory()->unactivated()->times(3)->create();
        Title::factory()->inactive()->times(3)->create();
        Title::factory()->retired()->times(3)->create();
    }
}
