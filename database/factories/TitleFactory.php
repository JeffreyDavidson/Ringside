<?php

use App\Models\Title;
use Faker\Generator as Faker;

$factory->define(Title::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence,
        'introduced_at' => today()->toDateTimeString(),
        'is_active' => true,
    ];
});
