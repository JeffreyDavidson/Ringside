<?php

declare(strict_types=1);

use App\Actions\TagTeams\CreateAction;
use App\Data\TagTeamData;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->tagTeamRepository = Mockery::mock(TagTeamRepository::class);
});

test('it creates a tag team without tag team partners and employment', function () {
    $data = new TagTeamData(
        'Example Tag Team Name',
        null,
        null,
        null,
        null
    );

    $this->tagTeamRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new TagTeam());

    $this->tagTeamRepository
        ->shouldNotReceive('addTagTeamPartner');

    $this->tagTeamRepository
        ->shouldNotReceive('employ');

    CreateAction::run($data);
});

test('it employs a tag team and tag team partners and employment when start date is present', function () {
    $datetime = now();
    [$wrestlerA, $wrestlerB] = Wrestler::factory()
        ->count(2)
        ->create();

    $data = new TagTeamData(
        'Example Tag Team Name',
        null,
        $datetime,
        $wrestlerA,
        $wrestlerB
    );

    $this->tagTeamRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns($tagTeam = new TagTeam());

    $this->tagTeamRepository
        ->shouldReceive('addTagTeamPartner')
        ->once()
        ->withArgs(function (TagTeam $tagTeamToAddWrestlers, Wrestler $wrestlerToAdd, Carbon $joinDate) use ($tagTeam, $wrestlerA, $datetime) {
            expect($tagTeamToAddWrestlers->is($tagTeam))->toBeTrue()
                ->and($wrestlerToAdd->is($wrestlerA))->toBeTrue()
                ->and($joinDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('addTagTeamPartner')
        ->once()
        ->withArgs(function (TagTeam $tagTeamToAddWrestlers, Wrestler $wrestlerToAdd, Carbon $joinDate) use ($tagTeam, $wrestlerB, $datetime) {
            expect($tagTeamToAddWrestlers->is($tagTeam))->toBeTrue()
                ->and($wrestlerToAdd->is($wrestlerB))->toBeTrue()
                ->and($joinDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('employ')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    CreateAction::run($data);
});
