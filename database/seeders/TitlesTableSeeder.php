<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Title;
use Illuminate\Database\Seeder;

class TitlesTableSeeder extends Seeder
{
    public function run(): void
    {
        Title::factory()->active()->count(3)->create();
        Title::factory()->withFutureActivation()->count(3)->create();
        Title::factory()->unactivated()->count(3)->create();
        Title::factory()->inactive()->count(3)->create();
        Title::factory()->retired()->count(3)->create();
    }
}
