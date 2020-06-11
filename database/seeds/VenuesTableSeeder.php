<?php

use Illuminate\Database\Seeder;
use Tests\Factories\VenueFactory;

class VenuesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($w = 1; $w <= 100; $w++) {
            VenueFactory::new()->create(['name' => 'Venue '.$w]);
        }
    }
}
