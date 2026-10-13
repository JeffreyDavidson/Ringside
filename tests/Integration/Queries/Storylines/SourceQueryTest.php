<?php

declare(strict_types=1);
use App\Actions\Matches\RecordResultAction;
use App\Actions\Storylines\RecordDevelopmentAction;
use App\Data\Matches\MatchResultData;
use App\Data\Storylines\DevelopmentData;
use App\Data\Storylines\SourceData;
use App\Enums\MatchFinish;
use App\Enums\Storylines\StepStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Queries\Storylines\SourceQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/../../../Fixtures/storylines.php';
test('stable match and segment identities show source evidence without treating it as a creative outcome', function (string $kind) {
    // Arrange
    $f = storylineSourceFixture();
    $source = new SourceData($f['event']->id, $kind === 'match' ? $f['match']->id : null, $kind === 'segment' ? $f['segment'] : null);
    $step = plannedStorylineStep($f['story'], $source);
    $content = $f['run']->content;
    foreach ($content['items'] as &$item) {
        $item['state'] = 'finished';
    }
    $f['run']->content = $content;
    $f['run']->save();
    // Act
    $evidence = resolve(SourceQuery::class)->get($source, $f['story']->promotion_id);
    // Assert
    expect($evidence['context']['live_state'])->toBe('finished')->and($step->refresh()->status)->toBe(StepStatus::Planned);
})->with(['match', 'segment']);
test('removed segment UUIDs never attach to a replacement with the same label', function () {
    $f = storylineSourceFixture();
    $source = new SourceData($f['event']->id, segmentId: $f['segment']);
    $old = resolve(SourceQuery::class)->get($source, $f['story']->promotion_id);
    $pack = $f['event']->pack()->firstOrFail();
    $content = $pack->content;
    foreach ($content['segments'] as &$segment) {
        $segment['id'] = (string) Str::uuid();
    }
    unset($segment);
    $pack->content = $content;
    $pack->save();
    $evidence = resolve(SourceQuery::class)->get($source, $f['story']->promotion_id);
    expect($evidence['warnings'])->toContain(__('storylines.removed_segment'))
        ->and($evidence['context']['segment_label'])->toBeNull()
        ->and($evidence['context']['live_label'])->toBe('Closing promo')
        ->and($evidence['fingerprint'])->not->toBe($old['fingerprint']);
});
test('deleted and unplaced sources are visible as historical evidence', function () {
    $f = storylineSourceFixture();
    $f['match']->delete();
    $segment = resolve(SourceQuery::class)->get(new SourceData($f['event']->id, segmentId: $f['segment']), $f['story']->promotion_id);
    $match = resolve(SourceQuery::class)->get(new SourceData($f['event']->id, matchId: $f['match']->id), $f['story']->promotion_id);
    $f['event']->delete();
    $event = resolve(SourceQuery::class)->get(new SourceData($f['event']->id), $f['story']->promotion_id);
    expect($segment['warnings'])->toContain(__('storylines.unplaced_segment'))
        ->and($match['warnings'])->toContain(__('storylines.deleted_source'))
        ->and($event['warnings'])->toContain(__('storylines.deleted_source'));
});
test('foreign events and foreign match IDs are refused with no new step', function (string $kind) {
    $f = storylineSourceFixture();
    $foreign = Event::factory()->for(Promotion::factory())->create();
    $other = EventMatch::factory()->for($foreign)->create();
    $source = $kind === 'event' ? new SourceData($foreign->id) : new SourceData($f['event']->id, $other->id);
    expect(fn () => plannedStorylineStep($f['story'], $source))->toThrow(ModelNotFoundException::class)
        ->and($f['story']->steps()->count())->toBe(0);
})->with(['event', 'match']);
test('exclusive source fields are validated', function (SourceData $source) {
    expect(fn () => resolve(SourceQuery::class)->get($source, 1))->toThrow(ValidationException::class);
})->with([
    'blank date' => [new SourceData(targetOn: '')],
    'blank segment' => [new SourceData(1, segmentId: '')],
    'match without event' => [new SourceData(matchId: 1)],
    'both match and segment' => [new SourceData(1, 1, '1f64fc86-5bbf-4a41-952d-b85800358f00')],
    'event and standalone date' => [new SourceData(1, targetOn: '2026-10-13')],
]);
test('a source changed since selection rejects a save before any storyline write', function () {
    $f = storylineSourceFixture();
    $source = new SourceData($f['event']->id);
    $fingerprint = resolve(SourceQuery::class)->get($source, $f['story']->promotion_id)['fingerprint'];
    $f['event']->name = 'Updated source';
    $f['event']->save();
    expect(fn () => plannedStorylineStep($f['story'], new SourceData($f['event']->id, fingerprint: $fingerprint)))->toThrow(StorylineConflictException::class)
        ->and($f['story']->steps()->count())->toBe(0)
        ->and($f['story']->revisions()->count())->toBe(0);
});
test('skipped sources require an explanation and never cancel the planned story automatically', function () {
    $f = storylineSourceFixture();
    $source = new SourceData($f['event']->id, segmentId: $f['segment']);
    $step = plannedStorylineStep($f['story'], $source);
    $content = $f['run']->content;
    foreach ($content['items'] as &$item) {
        if ($item['key'] === 'segment:'.$f['segment']) {
            $item['state'] = 'skipped';
        }
    }
    unset($item);
    $f['run']->content = $content;
    $f['run']->save();
    $data = new DevelopmentData('Backstage angle', 'The angle happened backstage.', today()->toDateString(), 1, [], $source, (string) Str::uuid(), $step->id);
    expect(fn () => resolve(RecordDevelopmentAction::class)->handle($f['story'], $f['story']->refresh()->version, $data))->toThrow(StorylineConflictException::class);
    $explained = new DevelopmentData($data->heading, $data->description, $data->occurredOn, 1, [], $source, (string) Str::uuid(), $step->id, 'The ring promo was skipped; the angle happened backstage.');
    $account = resolve(RecordDevelopmentAction::class)->handle($f['story'], $f['story']->refresh()->version, $explained);
    expect($account->source_context['live_state'])->toBe('skipped')->and($step->refresh()->status)->toBe(StepStatus::Happened);
});
test('an unscheduled event can be planned but cannot be reported as held', function () {
    $f = storylineSourceFixture();
    $f['event']->date = null;
    $f['event']->save();
    $source = new SourceData($f['event']->id);
    $step = plannedStorylineStep($f['story'], $source);
    $data = new DevelopmentData('Angle', 'It happened.', today()->toDateString(), 1, [], $source, (string) Str::uuid(), $step->id, 'Reviewed.');
    expect(fn () => resolve(RecordDevelopmentAction::class)->handle($f['story'], $f['story']->refresh()->version, $data))->toThrow(StorylineConflictException::class)
        ->and($step->refresh()->status)->toBe(StepStatus::Planned);
});

test('correcting only the winning side still changes the linked result evidence', function () {
    $f = storylineSourceFixture();
    $sides = $f['match']->sides()->orderBy('position')->get();
    resolve(RecordResultAction::class)->handle($f['match'], new MatchResultData(MatchFinish::Pinfall, $sides->firstOrFail(), collect()));
    $source = new SourceData($f['event']->id, $f['match']->id);
    $before = resolve(SourceQuery::class)->get($source, $f['story']->promotion_id);
    resolve(RecordResultAction::class)->handle($f['match'], new MatchResultData(MatchFinish::Pinfall, requiredModel($sides->last()), collect()));
    $after = resolve(SourceQuery::class)->get($source, $f['story']->promotion_id);
    expect($after['fingerprint'])->not->toBe($before['fingerprint']);
});
