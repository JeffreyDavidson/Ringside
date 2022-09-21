<?php

test('wrestlers of stable are synced when stable is updated', function () {
    $formerStableWrestlers = Wrestler::factory()->count(2)->create();
    $stable = Stable::factory()
        ->hasAttached($formerStableWrestlers, ['joined_at' => now()->toDateTimeString()])
        ->create();
    $newStableWrestlers = Wrestler::factory()->count(2)->create();

    $data = UpdateRequest::factory()->create([
        'wrestlers' => $newStableWrestlers->modelKeys(),
    ]);

    $this->actingAs(administrator())
        ->from(action([StablesController::class, 'edit'], $stable))
        ->patch(action([StablesController::class, 'update'], $stable), $data)
        ->assertRedirect(action([StablesController::class, 'index']));

    $allWrestlers = $formerStableWrestlers->merge($newStableWrestlers);

    expect($stable->fresh())
        // ->wrestlers->modelKeys()->toHaveCount(4)->collectionHas($newStableWrestlers->modelKeys())->collectionHas($formerStableWrestlers->modelKeys())
        ->wrestlers->toHaveCount(4)->toContain($allWrestlers->all())
        // ->currentWrestlers->modelKeys()->toHaveCount(2)->collectionHas($newStableWrestlers->modelKeys())->collectionDoesntHave($formerStableWrestlers->modelKeys());
        ->currentWrestlers->toHaveCount(2)->toBeCollection();
});

test('tag teams of stable are synced when stable is updated', function () {
    $formerStableTagTeams = TagTeam::factory()->count(2)->create();
    $stable = Stable::factory()
        ->hasAttached($formerStableTagTeams, ['joined_at' => now()->toDateTimeString()])
        ->create();
    $newStableTagTeams = TagTeam::factory()->count(2)->create();

    $data = UpdateRequest::factory()->create([
        'tag_teams' => $newStableTagTeams->modelKeys(),
    ]);

    $this->actingAs(administrator())
        ->from(action([StablesController::class, 'edit'], $stable))
        ->patch(action([StablesController::class, 'update'], $stable), $data)
        ->assertRedirect(action([StablesController::class, 'index']));

    expect($stable->fresh())
        ->tagTeams->toHaveCount(4)->toContain()
        ->currentTagTeams->toHaveCount(2);
});
