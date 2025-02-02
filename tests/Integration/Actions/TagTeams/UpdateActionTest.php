<?php

declare(strict_types=1);

use App\Actions\TagTeams\UpdateAction;
use App\Data\TagTeamData;
use App\Models\TagTeam;
use App\Models\TagTeamEmployment;
use App\Repositories\TagTeamRepository;

beforeEach(function () {
    $this->tagTeamRepository = $this->mock(TagTeamRepository::class);
});

test('it updates a tag team', function () {
    $data = new TagTeamData(
        'New Example Tag Team',
        null,
        null,
        null,
        null,
    );
    $tagTeam = TagTeam::factory()->create();

    $this->tagTeamRepository
        ->shouldReceive('update')
        ->once()
        ->with($tagTeam, $data)
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldNotReceive('employ');

    UpdateAction::run($tagTeam, $data);
});

test('it employs an unemployed tag team', function () {
    $data = new TagTeamData(
        'New Example Tag Team',
        null,
        now(),
        null,
        null,
    );
    $tagTeam = TagTeam::factory()->unemployed()->create();

    $this->tagTeamRepository
        ->shouldReceive('update')
        ->once()
        ->with($tagTeam, $data)
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('employ')
        ->once()
        ->with($tagTeam, $data->start_date)
        ->andReturns($tagTeam);

    UpdateAction::run($tagTeam, $data);
});

test('it employs a tag team with a future employment date', function () {
    $datetime = now()->addDays(2);
    $tagTeam = TagTeam::factory()
        ->has(TagTeamEmployment::factory()->started(now()->addMonth()), 'employments')
        ->create();
    $data = new TagTeamData(
        'New Example Tag Team',
        null,
        $datetime,
        null,
        null,
    );

    $this->tagTeamRepository
        ->shouldReceive('update')
        ->once()
        ->with($tagTeam, $data)
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('employ')
        ->once()
        ->with($tagTeam, $data->start_date)
        ->andReturns($tagTeam);

    UpdateAction::run($tagTeam, $data);
});
