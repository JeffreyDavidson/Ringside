<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TagTeam;
use Illuminate\Database\Seeder;

class TagTeamsTableSeeder extends Seeder
{
    public function run(): void
    {
        TagTeam::factory()->count(100)->bookable()->create();
        TagTeam::factory()->count(100)->unbookable()->create();
        TagTeam::factory()->count(20)->withFutureEmployment()->create();
        TagTeam::factory()->count(10)->suspended()->create();
        TagTeam::factory()->count(5)->retired()->create();
        TagTeam::factory()->count(5)->unemployed()->create();
        TagTeam::factory()->count(100)->released()->create();
    }
}
