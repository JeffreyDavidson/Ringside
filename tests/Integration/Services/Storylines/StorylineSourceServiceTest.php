<?php

declare(strict_types=1);
use App\Actions\Matches\RecordResultAction;
use App\Actions\Storylines\RecordDevelopmentAction;
use App\Data\Matches\MatchResultData;
use App\Data\Storylines\DevelopmentData;
use App\Data\Storylines\SourceData;
use App\Enums\Matches\MatchFinish;
use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineSourceWarning;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Services\Storylines\StorylineSourceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

require_once __DIR__.'/../../../Fixtures/storylines.php';

/** @return array{context: array<string, mixed>, fingerprint: string, warnings: list<StorylineSourceWarning>} */
function storylineEvidence(SourceData $source, int $promotionId): array
{
    return resolve(StorylineSourceService::class)->get($source, $promotionId) ?? throw new LogicException('Expected the source to exist');
}

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
    $evidence = storylineEvidence($source, $f['story']->promotion_id);
    // Assert
    expect($evidence['context']['live_state'])->toBe('finished')->and($step->refresh()->status)->toBe(StepStatus::Planned);
})->with(['match', 'segment']);
test('removed segment UUIDs never attach to a replacement with the same label', function () {
    $f = storylineSourceFixture();
    $source = new SourceData($f['event']->id, segmentId: $f['segment']);
    $old = storylineEvidence($source, $f['story']->promotion_id);
    $pack = $f['event']->pack()->firstOrFail();
    $content = $pack->content;
    foreach ($content['segments'] as &$segment) {
        $segment['id'] = (string) Str::uuid();
    }
    unset($segment);
    $pack->content = $content;
    $pack->save();
    $evidence = storylineEvidence($source, $f['story']->promotion_id);
    expect($evidence['warnings'])->toContain(StorylineSourceWarning::RemovedSegment)
        ->and($evidence['context']['segment_label'])->toBeNull()
        ->and($evidence['context']['live_label'])->toBe('Closing promo')
        ->and($evidence['fingerprint'])->not->toBe($old['fingerprint']);
});
test('deleted and unplaced sources are visible as historical evidence', function () {
    $f = storylineSourceFixture();
    $f['match']->delete();
    $segment = storylineEvidence(new SourceData($f['event']->id, segmentId: $f['segment']), $f['story']->promotion_id);
    $match = storylineEvidence(new SourceData($f['event']->id, matchId: $f['match']->id), $f['story']->promotion_id);
    $f['event']->delete();
    $event = storylineEvidence(new SourceData($f['event']->id), $f['story']->promotion_id);
    expect($segment['warnings'])->toContain(StorylineSourceWarning::UnplacedSegment)
        ->and($match['warnings'])->toContain(StorylineSourceWarning::DeletedSource)
        ->and($event['warnings'])->toContain(StorylineSourceWarning::DeletedSource);
});
test('a deleted match on a deleted event reports the deleted source once', function () {
    // Arrange
    $f = storylineSourceFixture();
    $f['match']->delete();
    $f['event']->delete();

    // Act
    $evidence = storylineEvidence(new SourceData($f['event']->id, matchId: $f['match']->id), $f['story']->promotion_id);

    // Assert
    expect($evidence['warnings'])->toBe([StorylineSourceWarning::DeletedSource]);
});
test('foreign events and foreign match IDs are refused with no new step', function (string $kind) {
    $f = storylineSourceFixture();
    $foreign = Event::factory()->for(Promotion::factory())->create();
    $other = EventMatch::factory()->for($foreign)->create();
    $source = $kind === 'event' ? new SourceData($foreign->id) : new SourceData($f['event']->id, $other->id);
    expect(fn () => plannedStorylineStep($f['story'], $source))->toThrow(ModelNotFoundException::class)
        ->and($f['story']->steps()->count())->toBe(0);
})->with(['event', 'match']);
test('a source changed since selection rejects a save before any storyline write', function () {
    $f = storylineSourceFixture();
    $source = new SourceData($f['event']->id);
    $fingerprint = storylineEvidence($source, $f['story']->promotion_id)['fingerprint'];
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
    expect(fn () => resolve(RecordDevelopmentAction::class)->handle($f['story'], $f['story']->refresh()->version, $data, signedInUser()))->toThrow(StorylineConflictException::class);
    $explained = new DevelopmentData($data->heading, $data->description, $data->occurredOn, 1, [], $source, (string) Str::uuid(), $step->id, 'The ring promo was skipped; the angle happened backstage.');
    $account = resolve(RecordDevelopmentAction::class)->handle($f['story'], $f['story']->refresh()->version, $explained, signedInUser());
    expect($account->source_context['live_state'])->toBe('skipped')->and($step->refresh()->status)->toBe(StepStatus::Happened);
});
test('an unscheduled event can be planned but cannot be reported as held', function () {
    $f = storylineSourceFixture();
    $f['event']->date = null;
    $f['event']->save();
    $source = new SourceData($f['event']->id);
    $step = plannedStorylineStep($f['story'], $source);
    $data = new DevelopmentData('Angle', 'It happened.', today()->toDateString(), 1, [], $source, (string) Str::uuid(), $step->id, 'Reviewed.');
    expect(fn () => resolve(RecordDevelopmentAction::class)->handle($f['story'], $f['story']->refresh()->version, $data, signedInUser()))->toThrow(StorylineConflictException::class)
        ->and($step->refresh()->status)->toBe(StepStatus::Planned);
});

test('correcting only the winning side still changes the linked result evidence', function () {
    $f = storylineSourceFixture();
    $sides = $f['match']->sides()->orderBy('position')->get();
    resolve(RecordResultAction::class)->handle($f['match'], new MatchResultData(MatchFinish::Pinfall, $sides->firstOrFail(), collect()), administrator());
    $source = new SourceData($f['event']->id, $f['match']->id);
    $before = storylineEvidence($source, $f['story']->promotion_id);
    resolve(RecordResultAction::class)->handle($f['match'], new MatchResultData(MatchFinish::Pinfall, requiredModel($sides->last()), collect()), administrator());
    $after = storylineEvidence($source, $f['story']->promotion_id);
    expect($after['fingerprint'])->not->toBe($before['fingerprint']);
});

test('a source without an event fingerprints only its target date', function () {
    // Act
    $dated = storylineEvidence(new SourceData(null, targetOn: '2026-10-20'), 1);
    $undated = storylineEvidence(new SourceData, 1);

    // Assert
    expect($dated)->toBe(['context' => [], 'fingerprint' => hash('sha256', '2026-10-20'), 'warnings' => []])
        ->and($undated['fingerprint'])->toBe('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855');
});

test('an event source fingerprints exactly the context it reports', function () {
    // Arrange
    $f = storylineSourceFixture();
    $event = $f['event'];

    // Act
    $evidence = storylineEvidence(new SourceData($event->id), $f['story']->promotion_id);

    // Assert
    $context = ['event_id' => $event->id, 'event_name' => 'Harbor Show', 'event_date' => $event->date?->toIso8601String(), 'event_deleted' => false,
        'event_timezone' => 'UTC', 'live_state' => null, 'live_label' => null];
    expect($evidence['context'])->toEqual($context)
        ->and($evidence['fingerprint'])->toBe(hash('sha256', json_encode($context, JSON_THROW_ON_ERROR)))
        ->and($evidence['warnings'])->toBeEmpty();
});

test('a source that names an event or match the promotion does not have yields nothing', function (string $kind) {
    // Arrange
    $f = storylineSourceFixture();
    $foreign = Event::factory()->for(Promotion::factory())->create();
    $other = EventMatch::factory()->for($foreign)->create();
    $source = $kind === 'event' ? new SourceData($foreign->id) : new SourceData($f['event']->id, $other->id);

    // Act
    $evidence = resolve(StorylineSourceService::class)->get($source, $f['story']->promotion_id);

    // Assert
    expect($evidence)->toBeNull();
})->with(['event', 'match']);

test('a loaded event and match are used without reading them again', function () {
    // Arrange
    $f = storylineSourceFixture();
    $event = Event::query()->with(['promotion', 'pack', 'liveRun', 'matches'])->findOrFail($f['event']->id);
    $match = EventMatch::query()->with(['competitors', 'titles'])->findOrFail($f['match']->id);
    $service = resolve(StorylineSourceService::class);

    // Act
    $statements = recordStatements(fn () => $service->get(new SourceData($event->id, $match->id, null), $f['story']->promotion_id, $event, $match));

    // Assert
    expect($statements)->toBeEmpty();
});
