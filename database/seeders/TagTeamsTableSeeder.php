<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Seeder;

class TagTeamsTableSeeder extends Seeder
{
    public function run(): void
    {
        $sequence = 0;
        $uniqueName = function () use (&$sequence): array {
            $sequence++;

            return ['name' => "Seeded Tag Team {$sequence}"];
        };

        TagTeam::factory()->count(100)->bookable()->state($uniqueName)->create();
        TagTeam::factory()->count(100)->unbookable()->state($uniqueName)->create();
        TagTeam::factory()->count(20)->withFutureEmployment()->state($uniqueName)->create();
        TagTeam::factory()->count(10)->suspended()->state($uniqueName)->create();
        TagTeam::factory()->count(5)->retired()->state($uniqueName)->create();
        TagTeam::factory()->count(5)->unemployed()->state($uniqueName)->create();
        TagTeam::factory()->count(100)->released()->state($uniqueName)->create();
    }
}
