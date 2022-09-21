<?php

use App\Actions\TagTeams\UpdateAction;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('updates a tag team and redirects', function () {
    $tagTeam = TagTeam::factory()->create(['name' => 'Old Tag Team Name']);

    $requestData = UpdateRequest::factory()->create([
        'name' => 'New Tag Team Name',
        'start_date' => null,
    ]);

    UpdateAction::run($requestData);

    expect($tagTeam->fresh())
        ->name->toBe('New Tag Team Name')
        ->employments->toBeEmpty();
});

test('wrestlers of tag team are synced when tag team is updated', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $formerTagTeamPartners = $tagTeam->currentWrestlers;
    [$newTagTeamPartnerA, $newTagTeamPartnerB] = Wrestler::factory()->bookable()->count(2)->create();

    $data = UpdateRequest::factory()->create([
        'wrestlerA' => $newTagTeamPartnerA->getKey(),
        'wrestlerB' => $newTagTeamPartnerB->getKey(),
    ]);

    UpdateAction::run($tagTeamData);

    expect($tagTeam->fresh())
        ->wrestlers->toHaveCount(4)
        ->currentWrestlers
            ->toHaveCount(2)
            ->toContain([$newTagTeamPartnerA, $newTagTeamPartnerB])
            ->not->toContain($formerTagTeamPartners->modelKeys());
});
