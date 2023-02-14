<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenuesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($w = 1; $w <= 100; $w++) {
            Venue::factory()->create(['name' => 'Venue '.$w]);
        }
    }
}
