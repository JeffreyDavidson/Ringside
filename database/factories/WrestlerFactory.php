<?php

use Carbon\Carbon;
use App\Models\Wrestler;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(Wrestler::class, function (Faker $faker) {
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

$factory->state(Wrestler::class, 'active', [
    'is_active' => true,
]);

$factory->state(Wrestler::class, 'future', [
    'hired_at' => Carbon::tomorrow()->toDateTimeString(),
]);

$factory->afterCreatingState(Wrestler::class, 'retired', function ($wrestler) {
    $wrestler->retire();
});

$factory->afterCreatingState(Wrestler::class, 'suspended', function ($wrestler) {
    $wrestler->suspend();
});

$factory->afterCreatingState(Wrestler::class, 'injured', function ($wrestler) {
    $wrestler->injure();
});

$factory->afterCreatingState(Wrestler::class, 'inactive', function ($wrestler) {
    $wrestler->deactivate();
});
