<?php

use Faker\Generator as Faker;

$factory->define(App\TagTeam::class, function (Faker $faker) {
    return [
        'name' => $faker->words(2, true),
        'hired_at' => now()->subDays(2)->toDateTimeString(),
        'is_active' => true,
    ];
});

$factory->afterCreating(App\TagTeam::class, function ($tagteam, $faker) {
    $tagteam->wrestlers()->attach(factory(App\Wrestler::class, 2)->create(['hired_at' => $tagteam->hired_at]));
});

$factory->state(App\TagTeam::class, 'active', [
    'is_active' => true,
]);

$factory->afterCreatingState(\App\TagTeam::class, 'suspended', function ($tagteam) {
    $tagteam->suspend();
});

$factory->afterCreatingState(\App\TagTeam::class, 'inactive', function ($tagteam) {
    $tagteam->deactivate();
});

$factory->afterCreatingState(\App\TagTeam::class, 'retired', function ($tagteam) {
    $tagteam->retire();
});
