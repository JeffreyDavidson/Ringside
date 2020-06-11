<?php

use Carbon\Carbon;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Enums\StableStatus;
use Faker\Generator as Faker;

$factory->define(Stable::class, function (Faker $faker) {
    return [
        'name' => $faker->words(2, true),
        'status' => $faker->word(),
    ];
});

$factory->afterCreatingState(Stable::class, 'pending-employment', function ($stable) {
    $startedAt = Carbon::tomorrow()->toDateTimeString();

    $stable->employ($startedAt);

    $stable->wrestlerHistory()->attach(factory(Wrestler::class)->states('bookable')->create(), ['joined_at' => $startedAt]);
    $stable->tagTeamHistory()->attach(factory(TagTeam::class)->states('bookable')->create(), ['joined_at' => $startedAt]);
});

$factory->state(Stable::class, 'active', function ($faker) {
    return [
        'status' => StableStatus::ACTIVE,
    ];
});

$factory->afterCreatingState(Stable::class, 'active', function ($stable) {
    $startedAt = Carbon::tomorrow()->toDateTimeString();

    $stable->employ($startedAt);

    $stable->wrestlerHistory()->attach(factory(Wrestler::class)->states('bookable')->create(), ['joined_at' => $startedAt]);
    $stable->tagTeamHistory()->attach(factory(TagTeam::class)->states('bookable')->create(), ['joined_at' => $startedAt]);
});
