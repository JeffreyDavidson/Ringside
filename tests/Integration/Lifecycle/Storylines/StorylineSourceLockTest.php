<?php

declare(strict_types=1);
use App\Data\Storylines\SourceData;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Lifecycle\Storylines\StorylineSourceLock;

require_once __DIR__.'/../../../Fixtures/storylines.php';
test('new plans cannot select deleted sources while explicitly explained history can retain them', function () {
    $f = storylineSourceFixture();
    $f['event']->delete();
    $source = new SourceData($f['event']->id);
    expect(fn () => resolve(StorylineSourceLock::class)->lock($source, $f['story']->promotion_id))->toThrow(StorylineConflictException::class);
    $evidence = resolve(StorylineSourceLock::class)->lock($source, $f['story']->promotion_id, true);
    expect($evidence['context']['event_deleted'])->toBeTrue();
});
test('source rows lock before the story mutation in the established event order', function () {
    $f = storylineSourceFixture();
    $statements = recordStatements(fn () => plannedStorylineStep($f['story'], new SourceData($f['event']->id, $f['match']->id)));
    $locked = collect($statements)->where('locked', true)->pluck('sql')->values()->all();
    expect($locked)->toHaveCount(5);

    foreach (['events', 'events_matches', 'event_packs', 'event_live_runs', 'storylines'] as $index => $table) {
        expect($locked[$index])->toContain('from "'.$table.'"');
    }
});

$needExplaining = [
    'deleted event' => [function (array $f): SourceData {
        $f['event']->delete();

        return new SourceData($f['event']->id);
    }],
    'removed segment' => [function (array $f): SourceData {
        $pack = $f['event']->pack()->firstOrFail();
        $content = $pack->content;
        $content['segments'][0]['id'] = '2c0ed2c4-61b7-4d2f-a2f6-3b0a0d3f0a11';
        $pack->content = $content;
        $pack->save();

        return new SourceData($f['event']->id, segmentId: $f['segment']);
    }],
    'unplaced segment' => [function (array $f): SourceData {
        $f['match']->delete();

        return new SourceData($f['event']->id, segmentId: $f['segment']);
    }],
];
$allowCurrentSave = [
    'undated event' => [function (array $f): SourceData {
        $f['event']->date = null;
        $f['event']->save();

        return new SourceData($f['event']->id);
    }],
    'skipped live item' => [function (array $f): SourceData {
        $content = $f['run']->content;

        foreach ($content['items'] as &$item) {
            $item['state'] = 'skipped';
        }

        unset($item);
        $f['run']->content = $content;
        $f['run']->save();

        return new SourceData($f['event']->id, segmentId: $f['segment']);
    }],
];
dataset('sources whose warnings need explaining', $needExplaining);
dataset('sources whose warnings allow a current save', $allowCurrentSave);
dataset('sources with a warning', [...$needExplaining, ...$allowCurrentSave]);

test('a current save is refused for a source whose warning needs explaining', function (Closure $prepare) {
    // Arrange
    $f = storylineSourceFixture();
    $source = $prepare($f);

    // Act
    $attempt = fn () => resolve(StorylineSourceLock::class)->lock($source, $f['story']->promotion_id);

    // Assert
    expect($attempt)->toThrow(StorylineConflictException::class, __('storylines.source_changed'));
})->with('sources whose warnings need explaining');

test('a current save is allowed for a source whose warning does not block it', function (Closure $prepare) {
    // Arrange
    $f = storylineSourceFixture();
    $source = $prepare($f);

    // Act
    $result = resolve(StorylineSourceLock::class)->lock($source, $f['story']->promotion_id);

    // Assert
    expect($result['warnings'])->toHaveCount(1);
})->with('sources whose warnings allow a current save');

test('a historical save is allowed for every warning', function (Closure $prepare) {
    // Arrange
    $f = storylineSourceFixture();
    $source = $prepare($f);

    // Act
    $result = resolve(StorylineSourceLock::class)->lock($source, $f['story']->promotion_id, historical: true);

    // Assert
    expect($result['warnings'])->not->toBeEmpty();
})->with('sources with a warning');
