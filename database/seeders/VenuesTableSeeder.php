<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class VenuesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Venue::factory()
            ->count(100)
            ->sequence(fn (Sequence $sequence) => ['name' => 'Venue '.$sequence->index])
            ->create();
    }
}
