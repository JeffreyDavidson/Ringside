<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Http\Controllers\TagTeams\EmployController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('invoke employs an unemployed tag team and their unemployed wrestlers and redirects', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->unemployed()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->unemployed()
        ->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->employments->toHaveCount(1)
        ->status->toMatchObject(TagTeamStatus::BOOKABLE)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->employments->toHaveCount(1);
            $wrestler->status->toMatchObject(WrestlerStatus::BOOKABLE);
        });
});

test('invoke employs an unemployed tag team with bookable wrestlers and redirects', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->unemployed()
        ->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->employments->toHaveCount(1)
        ->status->toMatchObject(TagTeamStatus::BOOKABLE)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->employments->toHaveCount(1);
            $wrestler->status->toMatchObject(WrestlerStatus::BOOKABLE);
        });
});

test('invoke employs a future employed tag team and their tag team partners and redirects', function () {
    $tagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $startDate = $tagTeam->employments->last()->started_at;

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->currentEmployment->started_at->toBeLessThan($startDate)
        ->status->toMatchObject(TagTeamStatus::BOOKABLE)
        ->currentWrestlers->each(function ($wrestler) use ($startDate) {
            $wrestler->currentEmployment->started_at->toBeLessThan($startDate);
            $wrestler->status->toMatchObject(WrestlerStatus::BOOKABLE);
        });
});

test('invoke employs a released tag team and their tag team partners redirects', function () {
    $tagTeam = TagTeam::factory()->released()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->employments->toHaveCount(2)
        ->status->toMatchObject(TagTeamStatus::BOOKABLE)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->employments->toHaveCount(2);
            $wrestler->status->toMatchObject(WrestlerStatus::BOOKABLE);
        });
});
