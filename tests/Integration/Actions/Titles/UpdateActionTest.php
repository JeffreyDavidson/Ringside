<?php

declare(strict_types=1);

use App\Actions\Titles\UpdateAction;
use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleType;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it updates a title', function () {
    $data = new TitleData('New Example Title', TitleType::Singles, null);
    $title = Title::factory()->create();

    resolve(UpdateAction::class)->handle($title, $data);

    $title->refresh();
    expect($title->name)->toBe('New Example Title');
    expect($title->type)->toBe(TitleType::Singles);
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
    expect($title->name)->toBe('New Example Title');
    expect($title->type)->toBe(TitleType::Singles);
    expect($title->activityPeriods)->toHaveCount(1);
    expect(requiredDate($title->activityPeriods->firstOrFail()->started_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it updates a title with future activation but does not create new debut since it already has debuted', function () {
    $datetime = now()->addDays(2);
    $data = new TitleData('New Example Title', TitleType::Singles, $datetime);
    $title = Title::factory()->active()->create();
    $originalActivityPeriodCount = $title->activityPeriods->count();

    resolve(UpdateAction::class)->handle($title, $data);

    $title->refresh();
    expect($title->name)->toBe('New Example Title');
    expect($title->type)->toBe(TitleType::Singles);
    // Should not create new activation since title already has debuted
    expect($title->activityPeriods)->toHaveCount($originalActivityPeriodCount);
});
