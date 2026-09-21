<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Seeder;

class WrestlersTableSeeder extends Seeder
{
    public function run(): void
    {
        $sequence = 0;
        $uniqueName = function () use (&$sequence): array {
            $sequence++;

            return ['name' => "Seeded Wrestler {$sequence}"];
        };

        Wrestler::factory()->count(100)->bookable()->state($uniqueName)->create();
        Wrestler::factory()->count(20)->withFutureEmployment()->state($uniqueName)->create();
        Wrestler::factory()->count(10)->suspended()->state($uniqueName)->create();
        Wrestler::factory()->count(5)->retired()->state($uniqueName)->create();
        Wrestler::factory()->count(5)->injured()->state($uniqueName)->create();
        Wrestler::factory()->count(5)->unemployed()->state($uniqueName)->create();
        Wrestler::factory()->count(100)->released()->state($uniqueName)->create();
    }
}
