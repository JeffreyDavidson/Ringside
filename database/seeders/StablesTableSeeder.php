<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Roster\Stables\Stable;
use Illuminate\Database\Seeder;

class StablesTableSeeder extends Seeder
{
    public function run(): void
    {
        $sequence = 0;
        $uniqueName = function () use (&$sequence): array {
            $sequence++;

            return ['name' => "Seeded Stable {$sequence}"];
        };

        Stable::factory()->count(10)->active()->state($uniqueName)->create();
        Stable::factory()->count(2)->withFutureActivation()->state($uniqueName)->create();
        Stable::factory()->count(5)->inactive()->state($uniqueName)->create();
        Stable::factory()->count(5)->retired()->state($uniqueName)->create();
        Stable::factory()->count(5)->unactivated()->state($uniqueName)->create();
    }
}
