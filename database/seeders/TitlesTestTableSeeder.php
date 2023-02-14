<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Title;
use Illuminate\Database\Seeder;

class TitlesTestTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Title::factory()->active()->times(3)->create();
        Title::factory()->withFutureActivation()->times(3)->create();
        Title::factory()->unactivated()->times(3)->create();
        Title::factory()->inactive()->times(3)->create();
        Title::factory()->retired()->times(3)->create();
    }
}
