<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use Illuminate\Database\Seeder;

class MatchesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have basic dependencies
        if (Event::count() === 0) {
            Event::factory()->count(3)->create();
        }

        // MatchType is now an enum, no seeding needed

        // Create some event matches
        EventMatch::factory()->count(10)->create();
    }
}
