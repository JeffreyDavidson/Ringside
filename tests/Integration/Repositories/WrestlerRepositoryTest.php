<?php

declare(strict_types=1);

use App\Data\WrestlerData;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Models\WrestlerEmployment;
use App\Repositories\WrestlerRepository;
use App\ValueObjects\Height;

test('creates a wrestler without a signature move', function () {
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', null, null);

    $wrestler = app(WrestlerRepository::class)->create($data);

    expect($wrestler)
        ->name->toEqual('Example Wrestler Name')
        ->height->toBeInstanceOf(Height::class)
        ->weight->toEqual(220)
        ->hometown->toEqual('Laraville, New York')
        ->signature_move->toBeNull();
});

test('creates a wrestler with a signature move', function () {
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', 'Powerbomb', null);

    $wrestler = app(WrestlerRepository::class)->create($data);

    expect($wrestler)
        ->name->toEqual('Example Wrestler Name')
        ->height->toBeInstanceOf(Height::class)
        ->weight->toEqual(220)
        ->hometown->toEqual('Laraville, New York')
        ->signature_move->toEqual('Powerbomb');
});

test('updates a wrestler with a signature move', function () {
    $wrestler = Wrestler::factory()->create();
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', 'Powerbomb', null);

    $wrestler = app(WrestlerRepository::class)->update($wrestler, $data);

    expect($wrestler)
        ->name->toEqual('Example Wrestler Name')
        ->height->toBeInstanceOf(Height::class)
        ->weight->toEqual(220)
        ->hometown->toEqual('Laraville, New York')
        ->signature_move->toEqual('Powerbomb');
});

test('deletes a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    app(WrestlerRepository::class)->delete($wrestler);

    expect($wrestler->fresh())
        ->deleted_at->not->toBeNull();
});

test('restores a wrestler', function () {
    $wrestler = Wrestler::factory()->trashed()->create();

    app(WrestlerRepository::class)->restore($wrestler);

    expect($wrestler->fresh())
        ->deleted_at->toBeNull();
});

test('employ a wrestler', function () {
    $wrestler = Wrestler::factory()->create();
    $datetime = now();

    $wrestler = app(WrestlerRepository::class)->employ($wrestler, $datetime);

    expect($wrestler->fresh())->employments->toHaveCount(1);
    expect($wrestler->fresh()->employments->first())->started_at->toEqual($datetime->toDateTimeString());
});

test('updates employment of a wrestler', function () {
    $datetime = now();
    $wrestler = Wrestler::factory()
        ->has(WrestlerEmployment::factory()->started($datetime->copy()->addDays(2)), 'employments')
        ->create();

    expect($wrestler->fresh())->employments->toHaveCount(1);
    expect($wrestler->fresh()->employments->first())
        ->started_at->toDateTimeString()->toEqual($datetime->copy()->addDays(2)->toDateTimeString());

    $wrestler = app(WrestlerRepository::class)->employ($wrestler, $datetime);

    expect($wrestler->fresh())->employments->toHaveCount(1);
    expect($wrestler->fresh()->employments->first())
        ->started_at->toDateTimeString()->toEqual($datetime->toDateTimeString());
});

test('release a wrestler', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $wrestler = app(WrestlerRepository::class)->release($wrestler, $datetime);

    expect($wrestler->fresh())->employments->toHaveCount(1);
    expect($wrestler->fresh()->employments->first())
        ->ended_at->toDateTimeString()->toEqual($datetime->toDateTimeString());
});

test('injure a wrestler', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $wrestler = app(WrestlerRepository::class)->injure($wrestler, $datetime);

    expect($wrestler->fresh())->injuries->toHaveCount(1);
    expect($wrestler->fresh()->injuries->first())
        ->started_at->toDateTimeString()->toEqual($datetime->toDateTimeString());
});

test('clear an injured wrestler', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $datetime = now();

    $wrestler = app(WrestlerRepository::class)->clearInjury($wrestler, $datetime);

    expect($wrestler->fresh())->injuries->toHaveCount(1);
    expect($wrestler->fresh()->injuries->first())
        ->ended_at->toDateTimeString()->toEqual($datetime->toDateTimeString());
});

test('retire a wrestler', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $wrestler = app(WrestlerRepository::class)->retire($wrestler, $datetime);

    expect($wrestler->fresh())->retirements->toHaveCount(1);
    expect($wrestler->fresh()->retirements->first())
        ->started_at->toDateTimeString()->toEqual($datetime->toDateTimeString());
});

test('unretire a wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $datetime = now();

    $wrestler = app(WrestlerRepository::class)->unretire($wrestler, $datetime);

    expect($wrestler->fresh())->retirements->toHaveCount(1);
    expect($wrestler->fresh()->retirements->first())
        ->ended_at->toDateTimeString()->toEqual($datetime->toDateTimeString());
});

test('suspend a wrestler', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $wrestler = app(WrestlerRepository::class)->suspend($wrestler, $datetime);

    expect($wrestler->fresh())->suspensions->toHaveCount(1);
    expect($wrestler->fresh()->suspensions->first())
        ->started_at->toDateTimeString()->toEqual($datetime->toDateTimeString());
});

test('reinstate a wrestler', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now();

    $wrestler = app(WrestlerRepository::class)->reinstate($wrestler, $datetime);

    expect($wrestler->fresh())->suspensions->toHaveCount(1);
    expect($wrestler->fresh()->suspensions->first())
        ->ended_at->toDateTimeString()->toEqual($datetime->toDateTimeString());
});

test('remove a wrestler from their current tag team', function () {
    $wrestler = Wrestler::factory()
        ->bookable()
        ->onCurrentTagTeam($tagTeam = TagTeam::factory()->bookable()->create())
        ->create();
    $datetime = now();

    expect($wrestler->fresh())->currentTagTeam->id->toBe($tagTeam->id);
    expect($wrestler->fresh()->tagTeams->first()->pivot)->left_at->toBeNull();

    app(WrestlerRepository::class)->removeFromCurrentTagTeam($wrestler, $datetime);

    expect($wrestler->fresh())->currentTagTeam->toBeNull();
    expect($wrestler->fresh()->tagTeams->first()->pivot)->left_at->not->toBeNull();
});

test('it can query available wrestlers that can join a new tag team', function () {
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $tagTeamWrestler = Wrestler::factory()->bookable()->onCurrentTagTeam()->create();

    $wrestlers = app(WrestlerRepository::class)->getAvailableWrestlersForNewTagTeam();

    expect($wrestlers)
        ->toHaveCount(3)
        ->collectionHas($bookableWrestler)
        ->collectionHas($unemployedWrestler)
        ->collectionHas($futureEmployedWrestler)
        ->collectionDoesntHave($injuredWrestler)
        ->collectionDoesntHave($suspendedWrestler)
        ->collectionDoesntHave($retiredWrestler)
        ->collectionDoesntHave($releasedWrestler)
        ->collectionDoesntHave($tagTeamWrestler);
});

test('it can query available wrestlers that can join an existing tag team', function () {
    $tagTeam = TagTeam::factory()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $tagTeamWrestler = Wrestler::factory()->bookable()->onCurrentTagTeam($tagTeam)->create();

    $wrestlers = app(WrestlerRepository::class)->getAvailableWrestlersForExistingTagTeam($tagTeam);

    expect($wrestlers)
        ->toHaveCount(4)
        ->collectionHas($bookableWrestler)
        ->collectionHas($unemployedWrestler)
        ->collectionHas($futureEmployedWrestler)
        ->collectionHas($tagTeamWrestler)
        ->collectionDoesntHave($injuredWrestler)
        ->collectionDoesntHave($suspendedWrestler)
        ->collectionDoesntHave($retiredWrestler)
        ->collectionDoesntHave($releasedWrestler);
});
