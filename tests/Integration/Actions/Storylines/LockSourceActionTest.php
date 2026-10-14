<?php

declare(strict_types=1);
use App\Actions\Storylines\LockSourceAction;
use App\Data\Storylines\SourceData;
use App\Exceptions\Storylines\StorylineConflictException;

require_once __DIR__.'/../../../Fixtures/storylines.php';
test('new plans cannot select deleted sources while explicitly explained history can retain them', function () {
    $f = storylineSourceFixture();
    $f['event']->delete();
    $source = new SourceData($f['event']->id);
    expect(fn () => resolve(LockSourceAction::class)->handle($source, $f['story']->promotion_id))->toThrow(StorylineConflictException::class);
    $evidence = resolve(LockSourceAction::class)->handle($source, $f['story']->promotion_id, true);
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
