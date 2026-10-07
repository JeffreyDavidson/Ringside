<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployCurrentWrestlersAction;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('it skips retired current wrestlers and employs the rest', function () {
    $tagTeam = TagTeam::factory()->unemployed()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();

    $tagTeam->wrestlers()->attach($retiredWrestler, ['joined_at' => now()->subMonth()]);
    $eligibleWrestlers = $tagTeam->currentWrestlers()
        ->whereKeyNot($retiredWrestler->id)
        ->get();

    resolve(EmployCurrentWrestlersAction::class)
        ->handle($tagTeam, now());

    expect($retiredWrestler->refresh()->currentEmployment()->exists())->toBeFalse()
        ->and($retiredWrestler->currentRetirement()->exists())->toBeTrue()
        ->and($eligibleWrestlers)->not->toBeEmpty();

    foreach ($eligibleWrestlers as $wrestler) {
        expect($wrestler->refresh()->currentEmployment()->exists())->toBeTrue();
    }
});

test('it employs unemployed current wrestlers', function () {
    $tagTeam = TagTeam::factory()->unemployed()->create();
    $employmentDate = now()->subDay();
    $wrestlers = $tagTeam->currentWrestlers()->get();
    $futureWrestler = Wrestler::factory()->withFutureEmployment()->create();

    $tagTeam->wrestlers()->attach($futureWrestler, ['joined_at' => now()->subMonth()]);

    resolve(EmployCurrentWrestlersAction::class)
        ->handle($tagTeam, $employmentDate);

    foreach ($wrestlers as $wrestler) {
        $wrestler->refresh();

        expect($wrestler->currentEmployment()->exists())->toBeTrue();

        $this->assertDatabaseHas('employments', [
            'employable_id' => $wrestler->id,
            'started_at' => $employmentDate->toDateTimeString(),
            'ended_at' => null,
        ]);
    }

    $futureWrestler->refresh();

    expect($futureWrestler->currentEmployment()->exists())->toBeFalse()
        ->and($futureWrestler->futureEmployment()->exists())->toBeTrue();
});
