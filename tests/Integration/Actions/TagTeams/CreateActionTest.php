<?php

declare(strict_types=1);

use App\Actions\TagTeams\CreateAction;
use App\Data\TagTeams\TagTeamData;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->tagTeamRepository = $this->mock(TagTeamRepository::class);
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

    resolve(CreateAction::class)->handle($data);
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
        ->shouldReceive('addWrestlers')
        ->once()
        ->withArgs(function (TagTeam $tagTeamToAddWrestlers, $wrestlers, Carbon $joinDate) use ($tagTeam, $datetime) {
            expect($tagTeamToAddWrestlers->is($tagTeam))->toBeTrue()
                ->and($joinDate->eq($datetime))->toBeTrue()
                ->and($wrestlers)->toHaveCount(2);

            return true;
        })
        ->andReturn($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->withArgs(function (TagTeam $employableTagTeam, Carbon $employmentDate) use ($tagTeam, $datetime) {
            expect($employableTagTeam->is($tagTeam))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(CreateAction::class)->handle($data);
});
