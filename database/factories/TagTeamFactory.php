<?php

use Carbon\Carbon;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Enums\TagTeamStatus;
use Faker\Generator as Faker;

$factory->define(TagTeam::class, function (Faker $faker) {
    return [
        'name' => $faker->words(2, true),
        'signature_move' => $faker->words(4, true),
        'status' => TagTeamStatus::PENDING_EMPLOYMENT,
    ];
});

$factory->state(TagTeam::class, 'bookable', function ($faker) {
    return [
        'status' => TagTeamStatus::BOOKABLE,
    ];
});

$factory->afterCreatingState(TagTeam::class, 'bookable', function ($tagTeam) {
    $tagTeam->employ();
    $tagTeam->addWrestlers(factory(Wrestler::class, 2)->states('bookable')->create()->modelKeys());
});


$factory->state(TagTeam::class, 'pending-employment', function ($faker) {
    return [
        'status' => TagTeamStatus::PENDING_EMPLOYMENT,
    ];
});

$factory->afterCreatingState(TagTeam::class, 'pending-employment', function ($tagTeam) {
    $tagTeam->employ(Carbon::tomorrow()->toDateTimeString());
    $tagTeam->addWrestlers(factory(Wrestler::class, 2)->states('bookable')->create()->modelKeys());
});


$factory->state(TagTeam::class, 'suspended', function ($faker) {
    return [
        'status' => TagTeamStatus::SUSPENDED,
    ];
});

$factory->afterCreatingState(TagTeam::class, 'suspended', function ($tagTeam) {
    $tagTeam->employ();
    $tagTeam->addWrestlers(factory(Wrestler::class, 2)->states('bookable')->create()->modelKeys());
    $tagTeam->suspend();
});

$factory->state(TagTeam::class, 'retired', function ($faker) {
    return [
        'status' => TagTeamStatus::RETIRED,
    ];
});

$factory->afterCreatingState(TagTeam::class, 'retired', function ($tagTeam) {
    $tagTeam->employ();
    $tagTeam->addWrestlers(factory(Wrestler::class, 2)->states('bookable')->create()->modelKeys());
    $tagTeam->retire();
});
