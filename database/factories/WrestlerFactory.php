<?php

use Carbon\Carbon;
use App\Models\Wrestler;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(Wrestler::class, function (Faker $faker) {
    $name = $faker->name;

    return [
        'name' => $name,
        'height' => $faker->numberBetween(60, 95),
        'weight' => $faker->numberBetween(180, 500),
        'hometown' => $faker->city . ', ' . $faker->state,
        'signature_move' => Str::title($faker->words(3, true)),
        'hired_at' => $faker->dateTime(),
    ];
});

$factory->state(Wrestler::class, 'bookable', [
    'hired_at' => Carbon::yesterday()->toDateTimeString(),
]);

$factory->state(Wrestler::class, 'pending-introduced', [
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
