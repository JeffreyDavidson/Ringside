<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createUnscheduledEvents();
        $this->createScheduledEvents();
        $this->createPastEvents();
    }

    public function createUnscheduledEvents(): void
    {
        Event::factory()
            ->unscheduled()
            ->count(10)
            ->create();
    }

    private function createScheduledEvents(): void
    {
        for ($x = 0; $x <= 5; $x++) {
            Event::factory()
                ->has(
                    EventMatch::factory()
                        ->sequence(fn (Sequence $sequence) => ['match_number' => $sequence->index + 1])
                        ->assigned()
                        ->count(8),
                    'matches'
                )
                ->scheduled()
                ->atRandomVenue()
                ->create();
        }
    }

    private function createPastEvents(): void
    {
        for ($x = 0; $x <= 100; $x++) {
            Event::factory()
                ->has(
                    EventMatch::factory()
                        ->state(new Sequence(
                            fn($sequence) => ['match_number' => $sequence->index + 1]
                        ))
                        ->count(8),
                    'matches'
                )
                ->past()
                ->atRandomVenue()
                ->create();
        }
    }
}
