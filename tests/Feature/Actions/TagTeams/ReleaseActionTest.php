<?php

use App\Actions\TagTeams\ReleaseAction;
use App\Events\TagTeams\TagTeamReleased;
use App\Exceptions\CannotBeReleasedException;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->tagTeamRepository = mock(TagTeamRepository::class);
});

test('it releases a bookable tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldNotReceive('reinstate');

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (TagTeam $releasedTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasedTagTeam->is($tagTeam))->toBeTrue();
            expect($releaseDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($tagTeam);

    ReleaseAction::run($tagTeam);

    Event::assertDispatched(TagTeamReleased::class, function ($event) use ($tagTeam, $datetime) {
        expect($event->tagTeam->is($tagTeam))->toBeTrue();
        expect($event->releaseDate->is($datetime))->toBeTrue();

        return true;
    });
});

test('it releases an bookable tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldNotReceive('reinstate');

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturn($tagTeam);

    ReleaseAction::run($tagTeam, $datetime);

    Event::assertDispatched(TagTeamReleased::class, function ($event) use ($tagTeam, $datetime) {
        expect($event->tagTeam->is($tagTeam))->toBeTrue();
        expect($event->releaseDate->is($datetime))->toBeTrue();

        return true;
    });
});

test('it releases a unbookable tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->unbookable()->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldNotReceive('reinstate');

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (TagTeam $releasedTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasedTagTeam->is($tagTeam))->toBeTrue();
            expect($releaseDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($tagTeam);

    ReleaseAction::run($tagTeam);

    Event::assertDispatched(TagTeamReleased::class, function ($event) use ($tagTeam, $datetime) {
        expect($event->tagTeam->is($tagTeam))->toBeTrue();
        expect($event->releaseDate->is($datetime))->toBeTrue();

        return true;
    });
});

test('it releases an unbookable tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->unbookable()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldNotReceive('reinstate');

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturn($tagTeam);

    ReleaseAction::run($tagTeam, $datetime);

    Event::assertDispatched(TagTeamReleased::class, function ($event) use ($tagTeam, $datetime) {
        expect($event->tagTeam->is($tagTeam))->toBeTrue();
        expect($event->releaseDate->is($datetime))->toBeTrue();

        return true;
    });
});

test('it releases a suspended tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (TagTeam $reinstatedTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($reinstatedTagTeam->is($tagTeam))->toBeTrue();
            expect($releaseDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (TagTeam $releasedTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasedTagTeam->is($tagTeam))->toBeTrue();
            expect($releaseDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($tagTeam);

    ReleaseAction::run($tagTeam);

    Event::assertDispatched(TagTeamReleased::class, function ($event) use ($tagTeam, $datetime) {
        expect($event->tagTeam->is($tagTeam))->toBeTrue();
        expect($event->releaseDate->is($datetime))->toBeTrue();

        return true;
    });
});

test('it releases an suspended tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturn($tagTeam);

    ReleaseAction::run($tagTeam, $datetime);

    Event::assertDispatched(TagTeamReleased::class, function ($event) use ($tagTeam, $datetime) {
        expect($event->tagTeam->is($tagTeam))->toBeTrue();
        expect($event->releaseDate->is($datetime))->toBeTrue();

        return true;
    });
});

test('invoke throws an exception for releasing a non releasable tag team', function ($factoryState) {
    $this->withoutExceptionHandling();

    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    ReleaseAction::run($tagTeam);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
