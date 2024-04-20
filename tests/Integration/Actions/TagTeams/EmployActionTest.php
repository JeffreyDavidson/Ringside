<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployAction;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->tagTeamRepository = $this->mock(TagTeamRepository::class);
});

test('it employs an unemployed tag team at the current datetime by default', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->unemployed()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->unemployed()
        ->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (TagTeam $employableTagTeam, Carbon $employmentDate) use ($tagTeam, $datetime) {
            expect($employableTagTeam->is($tagTeam))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    EmployAction::run($tagTeam);
});

test('it employs an unemployed tag team at a specific datetime', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->unemployed()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->unemployed()
        ->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('employ')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    EmployAction::run($tagTeam, $datetime);
});
