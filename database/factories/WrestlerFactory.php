<?php

use Carbon\Carbon;
use App\Models\Wrestler;
use Illuminate\Support\Str;
use App\Enums\WrestlerStatus;
use Faker\Generator as Faker;

$factory->define(Wrestler::class, function (Faker $faker) {
    $name = $faker->name;

    return [
        'name' => $name,
        'height' => $faker->numberBetween(60, 95),
        'weight' => $faker->numberBetween(180, 500),
        'hometown' => $faker->city . ', ' . $faker->state,
        'signature_move' => Str::title($faker->words(3, true)),
        'status' => WrestlerStatus::PENDING_EMPLOYMENT,
    ];
});

$factory->state(Wrestler::class, 'bookable', function ($faker) {
    return [
        'status' => WrestlerStatus::BOOKABLE,
    ];
});

$factory->afterCreatingState(Wrestler::class, 'bookable', function ($wrestler) {
    $wrestler->employ();
});

$factory->state(Wrestler::class, 'pending-employment', function ($faker) {
    return [
        'status' => WrestlerStatus::PENDING_EMPLOYMENT,
    ];
});

$factory->afterCreatingState(Wrestler::class, 'pending-employment', function ($wrestler) {
    $wrestler->employ(Carbon::tomorrow()->toDateTimeString());
});

$factory->state(Wrestler::class, 'retired', function ($faker) {
    return [
        'status' => WrestlerStatus::RETIRED,
    ];
});

$factory->afterCreatingState(Wrestler::class, 'retired', function ($wrestler) {
    $wrestler->employ();
    $wrestler->retire();
});

$factory->state(Wrestler::class, 'suspended', function ($faker) {
    return [
        'status' => WrestlerStatus::SUSPENDED,
    ];
});

$factory->afterCreatingState(Wrestler::class, 'suspended', function ($wrestler) {
    $wrestler->employ();
    $wrestler->suspend();
});

$factory->state(Wrestler::class, 'injured', function ($faker) {
    return [
        'status' => WrestlerStatus::INJURED,
    ];
});

$factory->afterCreatingState(Wrestler::class, 'injured', function ($wrestler) {
    $wrestler->employ();
    $wrestler->injure();
});
