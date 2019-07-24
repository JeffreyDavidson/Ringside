<?php

use Carbon\Carbon;
use App\Models\Manager;
use Faker\Generator as Faker;

$factory->define(Manager::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
    ];
});

$factory->afterCreatingState(Manager::class, 'bookable', function ($manager) {
    $manager->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);
});

$factory->afterCreatingState(Manager::class, 'pending-introduction', function ($manager) {
    $manager->employments()->create([
        'started_at' => Carbon::tomorrow()->toDateTimeString()
    ]);
});

$factory->afterCreatingState(Manager::class, 'retired', function ($manager) {
    $manager->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);

    $manager->retire();
});

$factory->afterCreatingState(Manager::class, 'suspended', function ($manager) {
    $manager->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);

    $manager->suspend();
});

$factory->afterCreatingState(Manager::class, 'injured', function ($manager) {
    $manager->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);

    $manager->injure();
});
