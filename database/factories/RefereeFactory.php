<?php

use App\Models\Referee;
use Faker\Generator as Faker;

$factory->define(Referee::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'hired_at' => today()->toDateTimeString(),
    ];
});

$factory->state(Referee::class, 'active', [
    'is_active' => true,
]);

$factory->afterCreatingState(Referee::class, 'retired', function ($wrestler) {
    $wrestler->retire();
});

$factory->afterCreatingState(Referee::class, 'injured', function ($referee) {
    $referee->injure();
});

$factory->afterCreatingState(Referee::class, 'inactive', function ($referee) {
    $referee->deactivate();
});
