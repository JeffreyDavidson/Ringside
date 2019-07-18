<?php

use Carbon\Carbon;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Faker\Generator as Faker;

$factory->define(TagTeam::class, function (Faker $faker) {
    return [
        'name' => $faker->words(2, true),
        'signature_move' => $faker->words(4, true),
    ];
});

$factory->afterCreating(TagTeam::class, function ($tagteam) {
    $tagteam->wrestlers()->attach(factory(Wrestler::class, 2)->states('bookable')->create());
});

$factory->afterCreatingState(TagTeam::class, 'bookable', function ($tagteam) {
    $tagteam->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);
});

$factory->afterCreatingState(TagTeam::class, 'pending-introduced', function ($tagteam) {
    $tagteam->employments()->create([
        'started_at' => Carbon::tomorrow()->toDateTimeString()
    ]);
});

$factory->afterCreatingState(TagTeam::class, 'suspended', function ($tagteam) {
    $tagteam->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);

    $tagteam->suspend();
});

$factory->afterCreatingState(TagTeam::class, 'retired', function ($tagteam) {
    $tagteam->employments()->create([
        'started_at' => Carbon::yesterday()->toDateTimeString()
    ]);

    $tagteam->retire();
});
