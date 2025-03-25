<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Manager;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Seeder;

class ManagersTableSeeder extends Seeder
{
    public function run(): void
    {
        Manager::factory()->count(20)->available()
            ->hasAttached(TagTeam::factory()->bookable(), ['hired_at' => now()])
            ->hasAttached(Wrestler::factory()->bookable(), ['hired_at' => now()])
            ->hasAttached(Stable::factory()->active(), ['hired_at' => now()])
            ->create();
        Manager::factory()->count(3)->injured()->create();
        Manager::factory()->count(5)->withFutureEmployment()->create();
        Manager::factory()->count(10)->released()->create();
        Manager::factory()->count(5)->retired()->create();
        Manager::factory()->count(3)->suspended()->create();
        Manager::factory()->count(10)->unemployed()->create();
    }
}
