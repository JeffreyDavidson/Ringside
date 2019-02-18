<?php

use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(\App\Wrestler::class, function (Faker $faker) {
    $name = $faker->name;

    return [
        'name' => $name,
        'height' => $faker->randomNumber(),
        'weight' => $faker->randomNumber(),
        'hometown' => $faker->city .', '.$faker->state,
        'hired_at' => $faker->dateTime(),
        'is_active' => true,
    ];
});

$factory->state(App\Wrestler::class, 'active', [
    'is_active' => true,
]);

$factory->afterCreatingState(\App\Wrestler::class, 'retired', function ($wrestler) {
    $wrestler->retire();
});

$factory->afterCreatingState(\App\Wrestler::class, 'suspended', function ($wrestler) {
    $wrestler->suspend();
});

$factory->afterCreatingState(\App\Wrestler::class, 'injured', function ($wrestler) {
    $wrestler->injure();
});

$factory->afterCreatingState(\App\Wrestler::class, 'inactive', function ($wrestler) {
    $wrestler->deactivate();
});
