<?php

declare(strict_types=1);

use App\Actions\TagTeams\RestoreAction;
use App\Models\TagTeams\TagTeam;
use App\Repositories\TagTeamRepository;

beforeEach(function () {
    $this->tagTeamRepository = $this->mock(TagTeamRepository::class);
});

test('it restores a deleted tag team', function () {
    $tagTeam = TagTeam::factory()->trashed()->create();

    $this->tagTeamRepository
        ->shouldReceive('restore')
        ->once()
        ->with($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('addWrestlers')
        ->zeroOrMoreTimes();

    $this->tagTeamRepository
        ->shouldReceive('addManagers')
        ->zeroOrMoreTimes();

    RestoreAction::run($tagTeam);
});
