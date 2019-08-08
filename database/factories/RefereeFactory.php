<?php

use Carbon\Carbon;
use App\Models\Referee;
use Faker\Generator as Faker;

$factory->define(Referee::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
    ];
});

$factory->afterCreatingState(Referee::class, 'bookable', function ($referee) {
    $referee->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);
});

$factory->afterCreatingState(Referee::class, 'retired', function ($referee) {
    $referee->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);

    $referee->retire();
});

$factory->afterCreatingState(Referee::class, 'injured', function ($referee) {
    $referee->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);

    $referee->injure();
});

$factory->afterCreatingState(Referee::class, 'suspended', function ($referee) {
    $referee->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);

    $referee->suspend();
});

$factory->afterCreatingState(Referee::class, 'pending-introduction', function ($referee) {
    $referee->employments()->create([
        'started_at' => Carbon::tomorrow()->toDateTimeString()
    ]);
});
