<?php

declare(strict_types=1);

use App\Actions\Managers\UnretireAction as UnretireManagerAction;
use App\Actions\TagTeams\UnretireCurrentMembersAction;
use App\Actions\Wrestlers\UnretireAction as UnretireWrestlerAction;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use JMac\Testing\Double;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it unretires retired current wrestlers and managers without employing them', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $manager = Manager::factory()->retired()->create();
    $unretirementDate = now()->subDay();

    $tagTeam->managers()->attach($manager, ['hired_at' => now()->subMonth()]);
    $wrestlers = $tagTeam->currentWrestlers()->get();

    resolve(UnretireCurrentMembersAction::class)
        ->handle($tagTeam, $unretirementDate);

    foreach ($wrestlers as $wrestler) {
        $wrestler->refresh();

        expect($wrestler->isRetired())->toBeFalse()
            ->and($wrestler->isEmployed())->toBeFalse();

        $this->assertDatabaseHas('retirements', [
            'retirable_id' => $wrestler->id,
            'retirable_type' => $wrestler->getMorphClass(),
            'ended_at' => $unretirementDate->toDateTimeString(),
        ]);
    }

    $manager->refresh();

    expect($manager->isRetired())->toBeFalse()
        ->and($manager->isEmployed())->toBeFalse();

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'ended_at' => $unretirementDate->toDateTimeString(),
    ]);
});

test('it skips current members rejected by unretirement rules', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->retired()->create();
    $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => now()->subMonth()]);
    $unretireWrestler = Double::for(UnretireWrestlerAction::class);
    $unretireManager = Double::for(UnretireManagerAction::class);
    $unretireWrestler->expects('handle')
        ->throws(CannotBeUnretiredException::notRetired($wrestler));

    (new UnretireCurrentMembersAction($unretireWrestler, $unretireManager))
        ->handle($tagTeam, now());

    $unretireWrestler->verify();
    $unretireManager->unused();
});

test('it does not swallow programmer errors while unretiring current members', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->retired()->create();
    $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => now()->subMonth()]);
    $unretireWrestler = Double::for(UnretireWrestlerAction::class);
    $unretireManager = Double::for(UnretireManagerAction::class);
    $unretireWrestler->expects('handle')
        ->throws(new LogicException('Unexpected unretirement failure.'));
    $action = new UnretireCurrentMembersAction($unretireWrestler, $unretireManager);

    expect(fn () => $action->handle($tagTeam, now()))
        ->toThrow(LogicException::class, 'Unexpected unretirement failure.');

    $unretireWrestler->verify();
    $unretireManager->unused();
});
