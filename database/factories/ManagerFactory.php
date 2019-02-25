<?php

use Faker\Generator as Faker;

$factory->define(\App\Manager::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'hired_at' => $faker->dateTime(),
        'is_active' => true,
    ];
});

$factory->state(App\Manager::class, 'active', [
    'is_active' => true,
]);

$factory->afterCreatingState(\App\Manager::class, 'retired', function ($manager) {
    $manager->retire();
});

$factory->afterCreatingState(\App\Manager::class, 'suspended', function ($manager) {
    $manager->suspend();
});

$factory->afterCreatingState(\App\Manager::class, 'injured', function ($manager) {
    $manager->injure();
});

$factory->afterCreatingState(\App\Manager::class, 'inactive', function ($manager) {
    $manager->deactivate();
});
