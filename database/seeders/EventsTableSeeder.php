<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Events\Event;
use Illuminate\Database\Seeder;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sequence = 0;
        $uniqueName = function () use (&$sequence): array {
            $sequence++;

            return ['name' => "Seeded Event {$sequence}"];
        };

        Event::factory()->scheduled()->count(5)->state($uniqueName)->create();
        Event::factory()->past()->count(100)->state($uniqueName)->create();
    }
}
