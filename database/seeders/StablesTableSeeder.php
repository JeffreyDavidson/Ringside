<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Stable;
use Illuminate\Database\Seeder;

class StablesTableSeeder extends Seeder
{
    public function run(): void
    {
        Stable::factory()->count(10)->active()->create();
        Stable::factory()->count(2)->withFutureActivation()->create();
        Stable::factory()->count(5)->inactive()->create();
        Stable::factory()->count(5)->retired()->create();
        Stable::factory()->count(5)->unactivated()->create();
    }
}
