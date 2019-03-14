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

$factory->state(Title::class, 'active', [
    'is_active' => true,
]);

$factory->afterCreatingState(Title::class, 'retired', function ($title) {
    $title->retire();
});

$factory->afterCreatingState(Title::class, 'inactive', function ($title) {
    $title->deactivate();
});

