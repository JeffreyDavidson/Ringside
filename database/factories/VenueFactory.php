<?php

use App\Models\Venue;
use Faker\Generator as Faker;

$factory->define(Venue::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'address1' => $faker->streetAddress,
        'address2' => $faker->optional()->secondaryAddress,
        'city' => $faker->city,
        'state' => $faker->state,
        'zip' => $faker->postcode,
    ];
});
