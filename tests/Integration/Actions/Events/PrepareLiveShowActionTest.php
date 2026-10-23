<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Actions\Events\CompleteLiveShowAction;
use App\Actions\Events\MarkLiveAttendanceAction;
use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\ReviewLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Data\Events\EventPackData;
use App\Data\Events\LiveShowReviewData;
use App\Enums\Events\LiveAttendanceMark;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ViewModels\Events\EventPackViewModel;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

test('preparation saves a reviewed live snapshot without changing the event pack', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()]);
    $match = EventMatch::factory()->for($event)->withCompetitors(Wrestler::factory()->count(2)->bookable()->create()->all())->withReferees()->create(['match_finish' => null]);
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $original = $event->pack()->firstOrFail()->content;

    // Act
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], signedInUser());

    // Assert
    expect($run->version)->toBe(1)
        ->and($run->content['items'][0]['key'])->toBe('match:'.$match->id)
        ->and($run->content['items'][0]['state'])->toBe('pending')
        ->and($run->content['roster'])->toHaveCount(3)
        ->and($run->event->is($event))->toBeTrue()
        ->and($event->pack()->firstOrFail()->content)->toBe($original);
});

test('arrival and briefing persist and a stale operator cannot replace them', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()]);
    EventMatch::factory()->for($event)->withCompetitors(Wrestler::factory()->count(2)->bookable()->create()->all())->withReferees()->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], signedInUser());
    $key = $run->content['roster'][0]['key'];
    $action = resolve(MarkLiveAttendanceAction::class);

    // Act
    $action->handle($event, 1, $key, LiveAttendanceMark::Arrived, true, signedInUser());
    $action->handle($event, 2, $key, LiveAttendanceMark::Briefed, true, signedInUser());

    // Assert
    expect($run->refresh()->content['roster'][0]['arrived'])->not->toBeNull()
        ->and($run->refresh()->content['roster'][0]['briefed'])->not->toBeNull();
    expect(fn () => $action->handle($event, 1, $key, LiveAttendanceMark::Arrived, true, signedInUser()))
        ->toThrow(RecordChangedException::class)
        ->and($run->refresh()->version)->toBe(3);
});

test('the show runs one item at a time and completion never records a match result', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()]);
    $match = EventMatch::factory()->for($event)->withCompetitors(Wrestler::factory()->count(2)->bookable()->create()->all())->withReferees()->create(['match_finish' => null]);
    $data = resolve(EventPackViewModel::class)->data($event);
    $content = [...$data['content'], 'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Intermission', 'after_match_id' => $match->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => 15, 'notes' => null]]];
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($content), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], signedInUser());
    $action = resolve(TransitionLiveItemAction::class);
    $first = $run->content['items'][0]['key'];
    $second = $run->content['items'][1]['key'];

    // Act
    $action->handle($event, 1, $first, LiveItemOperation::Start, signedInUser());

    // Assert
    expect(fn () => $action->handle($event, 2, $second, LiveItemOperation::Start, signedInUser()))
        ->toThrow(LiveShowException::class);

    // Act
    $action->handle($event, 2, $first, LiveItemOperation::Finish, signedInUser());
    $action->handle($event, 3, $second, LiveItemOperation::Skip, signedInUser(), 'Cancelled intermission');
    resolve(CompleteLiveShowAction::class)->handle($event, 4, signedInUser());

    // Assert
    expect($run->refresh()->status)->toBe(LiveShowStatus::Completed)
        ->and($run->refresh()->content['items'][0]['finish'])->not->toBeNull()
        ->and($run->refresh()->content['items'][1]['reason'])->toBe('Cancelled intermission')
        ->and($match->fresh()->match_finish)->toBe($match->match_finish);
});

test('review adopts pending plan changes while retaining actual times and attendance', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()]);
    EventMatch::factory()->for($event)->withCompetitors(Wrestler::factory()->count(2)->bookable()->create()->all())->withReferees()->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], signedInUser());
    resolve(MarkLiveAttendanceAction::class)->handle($event, 1, $run->content['roster'][0]['key'], LiveAttendanceMark::Arrived, true, signedInUser());
    resolve(TransitionLiveItemAction::class)->handle($event, 2, $run->content['items'][0]['key'], LiveItemOperation::Start, signedInUser());
    $before = $run->refresh()->content;
    $segment = ['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Closing promo', 'after_match_id' => null, 'planned_at' => null, 'revised_at' => null, 'minutes' => 10, 'notes' => 'Thank the crowd'];
    resolve(SaveEventPackAction::class)->handle($event, 1, $data['card']['fingerprint'], EventPackData::fromValidated([...$data['content'], 'segments' => [$segment]]), false);

    // Act
    resolve(ReviewLiveShowAction::class)->handle($event, 3, 2, $data['card']['fingerprint'], LiveShowReviewData::fromValidated([]), signedInUser());

    // Assert
    expect($run->refresh()->content['items'][0])->toBe($before['items'][0])
        ->and($run->refresh()->content['items'][1]['label'])->toBe('Closing promo')
        ->and($run->refresh()->content['roster'][0]['arrived'])->toBe($before['roster'][0]['arrived'])
        ->and($run->refresh()->pack_version)->toBe(2);
});

test('repeated preparation and outdated pack previews never replace a live record', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $pack = $fixture['event']->pack()->firstOrFail();

    // Act / Assert
    expect(fn () => resolve(PrepareLiveShowAction::class)->handle($fixture['event'], 0, 1, $pack->card_fingerprint, administrator()))->toThrow(RecordChangedException::class);
    expect($fixture['event']->liveRun()->count())->toBe(1)
        ->and($fixture['event']->liveRun()->firstOrFail()->version)->toBe(1);
});

test('a new live show requires a dated valid reviewed pack', function (string $case): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => $case === 'undated' ? null : now()]);

    if ($case !== 'empty') {
        EventMatch::factory()->for($event)->create();
    }

    $data = resolve(EventPackViewModel::class)->data($event);

    if ($case !== 'missing pack') {
        resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    }

    if ($case === 'unreviewed') {
        $event->update(['name' => 'Renamed after pack review']);
    }

    if ($case === 'old') {
        $event->update(['date' => now()->subDay()]);
        $data = resolve(EventPackViewModel::class)->data($event);
        resolve(SaveEventPackAction::class)->handle($event, 1, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), true);
    }

    $version = $case === 'old' ? 2 : 1;

    // Act / Assert
    expect(fn () => resolve(PrepareLiveShowAction::class)->handle($event, 0, $version, $data['card']['fingerprint'], signedInUser()))->toThrow(LiveShowException::class);
    expect($event->liveRun()->exists())->toBeFalse();
})->with(['missing pack', 'undated', 'empty', 'unreviewed', 'old']);

test('a stale preparation preview cannot create a run from a different pack', function (bool $staleVersion): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()]);
    EventMatch::factory()->for($event)->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $version = $staleVersion ? 0 : 1;
    $fingerprint = $staleVersion ? $data['card']['fingerprint'] : str_repeat('0', 64);

    // Act / Assert
    expect(fn () => resolve(PrepareLiveShowAction::class)->handle($event, 0, $version, $fingerprint, signedInUser()))->toThrow(RecordChangedException::class, __('core.errors.record.changed'));
    expect($event->liveRun()->exists())->toBeFalse()
        ->and(Activity::query()->where('log_name', 'live-show')->count())->toBe(0);
})->with(['outdated pack version' => true, 'different card fingerprint' => false]);

test('a prepared run starts as prepared with no completion time', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()]);
    EventMatch::factory()->for($event)->withCompetitors(Wrestler::factory()->count(2)->bookable()->create()->all())->withReferees()->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);

    // Act
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], signedInUser());

    // Assert
    expect($run->status)->toBe(LiveShowStatus::Prepared)
        ->and($run->completed_at)->toBeNull();
});

test('preparing a live show names the actor passed in as the audit causer', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $event = Event::factory()->create(['date' => now()]);
    EventMatch::factory()->for($event)->withCompetitors(Wrestler::factory()->count(2)->bookable()->create()->all())->withReferees()->create(['match_finish' => null]);
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $since = lastActivityId();

    // Act
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], $actor);

    // Assert
    expect(activityEntriesAfter($since, 'live-show'))->toBe(['prepare|'.causerTag($actor)]);
});
