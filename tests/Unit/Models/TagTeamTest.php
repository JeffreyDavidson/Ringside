<?php

use App\Enums\TagTeamStatus;
use App\Models\TagTeam;

test('a tag team has a name', function () {
    $tagTeam = TagTeam::factory()->create(['name' => 'Example Tag Team Name']);

    expect($tagTeam)->name->toBe('Example Tag Team Name');
});

test('a tag team can have a signature move', function () {
    $tagTeam = TagTeam::factory()->create(['signature_move' => 'Example Signature Move']);

    expect($tagTeam)->signature_move->toBe('Example Signature Move');
});

test('a tag team has a status', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam)->status->toBeInstanceOf(TagTeamStatus::class);
});
