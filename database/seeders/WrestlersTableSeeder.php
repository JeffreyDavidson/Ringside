<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Wrestler;
use Illuminate\Database\Seeder;

class WrestlersTableSeeder extends Seeder
{
    public function run(): void
    {
        Wrestler::factory()->count(100)->bookable()->create();
        Wrestler::factory()->count(20)->withFutureEmployment()->create();
        Wrestler::factory()->count(10)->suspended()->create();
        Wrestler::factory()->count(5)->retired()->create();
        Wrestler::factory()->count(5)->injured()->create();
        Wrestler::factory()->count(5)->unemployed()->create();
        Wrestler::factory()->count(100)->released()->create();
    }
}
