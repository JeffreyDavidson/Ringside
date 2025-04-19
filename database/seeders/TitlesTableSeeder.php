<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Title;
use Illuminate\Database\Seeder;

class TitlesTableSeeder extends Seeder
{
    public function run(): void
    {
        Title::factory()->active()->singles()->count(5)->create();
        Title::factory()->active()->tagTeam()->count(2)->create();

        Title::factory()->withFutureActivation()->singles()->count(1)->create();
        Title::factory()->withFutureActivation()->tagTeam()->count(1)->create();

        Title::factory()->unactivated()->singles()->count(1)->create();
        Title::factory()->unactivated()->tagTeam()->count(1)->create();

        Title::factory()->inactive()->singles()->count(1)->create();
        Title::factory()->inactive()->tagTeam()->count(1)->create();

        Title::factory()->retired()->singles()->count(1)->create();
        Title::factory()->retired()->tagTeam()->count(1)->create();
    }
}
