<?php

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenuesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($w = 1; $w <= 300; $w++) {
            factory(Venue::class)->create([
                'name' => 'Venue '.$w,
            ]);
        }
    }
}
