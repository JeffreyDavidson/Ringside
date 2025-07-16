<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchType;
use Illuminate\Database\Seeder;

class EventMatchSeeder extends Seeder
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
        
        if (MatchType::count() === 0) {
            MatchType::factory()->count(5)->create();
        }
        
        // Create some event matches
        EventMatch::factory()->count(10)->create();
    }
}
