<?php

declare(strict_types=1);

use App\Actions\Titles\UpdateAction;
use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleType;
use App\Exceptions\Titles\CannotChangeTypeException;
use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it updates a title', function () {
    $data = new TitleData('New Example Title', TitleType::Singles, null);
    $title = Title::factory()->create();

    resolve(UpdateAction::class)->handle($title, $data);

    $title->refresh();
    expect($title->name)->toBe('New Example Title')
        ->and($title->type)->toBe(TitleType::Singles);
});

test('it updates using the current persisted title state', function () {
    $title = Title::factory()->unactivated()->create(['name' => 'Original Title']);
    $staleTitle = $title->replicate(['id']);
    $staleTitle->id = $title->id;
    $staleTitle->exists = true;

    $updatedTitle = resolve(UpdateAction::class)->handle(
        $staleTitle,
        new TitleData('Updated From Stale State', TitleType::Singles, null),
    );
    $persistedTitle = Title::query()
        ->whereKey($title->getKey())
        ->firstOrFail();

    expect($updatedTitle->name)->toBe('Updated From Stale State')
        ->and($persistedTitle->name)->toBe('Updated From Stale State');
});

test('it activates an unactivated title if activation date is filled in request', function () {
    $datetime = now();
    $data = new TitleData('New Example Title', TitleType::Singles, $datetime);
    $title = Title::factory()->unactivated()->create();

    resolve(UpdateAction::class)->handle($title, $data);

    $title->refresh();
    expect($title->name)->toBe('New Example Title')
        ->and($title->type)->toBe(TitleType::Singles)
        ->and($title->activityPeriods)->toHaveCount(1)
        ->and(requiredDate($title->activityPeriods->firstOrFail()->started_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it updates a title with future activation but does not create new debut since it already has debuted', function () {
    $datetime = now()->addDays(2);
    $data = new TitleData('New Example Title', TitleType::Singles, $datetime);
    $title = Title::factory()->active()->create();
    $originalActivityPeriodCount = $title->activityPeriods->count();

    resolve(UpdateAction::class)->handle($title, $data);

    $title->refresh();
    expect($title->name)->toBe('New Example Title')
        ->and($title->type)->toBe(TitleType::Singles);
    // Should not create new activation since title already has debuted
    expect($title->activityPeriods)->toHaveCount($originalActivityPeriodCount);
});

test('it rejects changing the type of a title that has a championship reign', function () {
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->current()->create();

    $update = fn () => resolve(UpdateAction::class)->handle($title, new TitleData('Renamed Title', TitleType::TagTeam, null));

    expect($update)->toThrow(CannotChangeTypeException::class)
        ->and($title->refresh()->type)->toBe(TitleType::Singles)
        ->and($title->name)->not->toBe('Renamed Title');
});

test('it rejects changing the type of a title that is booked in a match', function () {
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    EventMatch::factory()->create()->titles()->attach($title);

    $update = fn () => resolve(UpdateAction::class)->handle($title, new TitleData('Renamed Title', TitleType::TagTeam, null));

    expect($update)->toThrow(CannotChangeTypeException::class)
        ->and($title->refresh()->type)->toBe(TitleType::Singles);
});

test('it still renames a title with reigns and bookings when the type is unchanged', function () {
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->current()->create();
    EventMatch::factory()->create()->titles()->attach($title);

    resolve(UpdateAction::class)->handle($title, new TitleData('Renamed Title', TitleType::Singles, null));

    expect($title->refresh()->name)->toBe('Renamed Title');
});

test('it allows changing the type once the only booking was deleted and no reign exists', function () {
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $match = EventMatch::factory()->create();
    $match->titles()->attach($title);
    $match->delete();

    resolve(UpdateAction::class)->handle($title, new TitleData('Renamed Title', TitleType::TagTeam, null));

    expect($title->refresh()->type)->toBe(TitleType::TagTeam);
});
