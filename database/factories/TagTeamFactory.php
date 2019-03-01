<?php

use App\Models\TagTeam;
use Faker\Generator as Faker;

$factory->define(TagTeam::class, function (Faker $faker) {
    return [
        'name' => $faker->words(2, true),
        'hired_at' => now()->subDays(2)->toDateTimeString(),
        'is_active' => true,
    ];
});

$factory->afterCreating(TagTeam::class, function ($tagteam, $faker) {
    $tagteam->wrestlers()->attach(factory(App\Models\Wrestler::class, 2)->create(['hired_at' => $tagteam->hired_at]));
});

$factory->state(TagTeam::class, 'active', [
    'is_active' => true,
]);

$factory->afterCreatingState(TagTeam::class, 'suspended', function ($tagteam) {
    $tagteam->suspend();
});

$factory->afterCreatingState(TagTeam::class, 'inactive', function ($tagteam) {
    $tagteam->deactivate();
});

$factory->afterCreatingState(TagTeam::class, 'retired', function ($tagteam) {
    $tagteam->retire();
});
