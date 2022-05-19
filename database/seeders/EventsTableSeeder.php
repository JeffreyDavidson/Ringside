<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $eNum = 1;

        for ($w = 1; $w <= 5; $w++) {
            Event::factory()->scheduled()->create([
                'name' => 'Event '.$eNum,
            ]);

            $eNum++;
        }

        Event::factory()->past()->create([
            'name' => 'Title '.$eNum,
        ]);

        $eNum++;
    }
}
