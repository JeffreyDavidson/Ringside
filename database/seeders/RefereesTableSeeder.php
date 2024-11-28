<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Referee;
use Illuminate\Database\Seeder;

class RefereesTableSeeder extends Seeder
{
    public function run(): void
    {
        Referee::factory()->count(20)->bookable()->create();
        Referee::factory()->count(3)->injured()->create();
        Referee::factory()->count(3)->withFutureEmployment()->create();
        Referee::factory()->count(10)->released()->create();
        Referee::factory()->count(3)->retired()->create();
        Referee::factory()->count(3)->suspended()->create();
        Referee::factory()->count(3)->unemployed()->create();
    }
}
