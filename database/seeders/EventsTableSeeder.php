<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;
use Tests\Factories\EventFactory;

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

        EventFactory::new()->past()->create([
            'name' => 'Title '.$eNum,
        ]);

        $eNum++;
    }
}
