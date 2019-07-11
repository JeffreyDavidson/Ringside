<?php

use App\Models\Title;
use Faker\Generator as Faker;

$factory->define(Title::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence,
        'introduced_at' => today()->toDateTimeString(),
    ];
});

$factory->state(Title::class, 'bookable', function ($faker) {
    return [
        'introduced_at' => $faker->dateTimeBetween('-1 week', '-1 day')
    ];
});

$factory->state(Title::class, 'pending-introduced', function ($faker) {
    return [
        'introduced_at' => $faker->dateTimeBetween('+1 day', '+1 month')
    ];
});

$factory->afterCreatingState(Title::class, 'retired', function ($title) {
    $title->retire();
});
