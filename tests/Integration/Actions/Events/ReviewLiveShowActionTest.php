<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Actions\Events\MarkLiveAttendanceAction;
use App\Actions\Events\ReviewLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Enums\Events\LiveAttendanceMark;
use App\Enums\Events\LiveItemOperation;
use App\Exceptions\Events\LiveShowException;
use App\Exceptions\RecordChangedException;
use App\Models\Matches\EventMatch;
use App\ViewModels\EventPackViewModel;

test('removed pending matches need an explicit skip and retained attendance is labelled unassigned', function (): void {
    // Arrange
    $fixture = liveShowFixture(segment: true);
    resolve(MarkLiveAttendanceAction::class)->handle($fixture['event'], 1, $fixture['person'], LiveAttendanceMark::Arrived, true);
    EventMatch::query()->where('event_id', $fixture['event']->id)->firstOrFail()->delete();
    $data = resolve(EventPackViewModel::class)->data($fixture['event']);
    $content = $data['content'];
    $content['segments'] = array_map(fn (array $segment): array => [...$segment, 'after_match_id' => null], $content['segments']);
    resolve(SaveEventPackAction::class)->handle($fixture['event'], 1, $data['card']['fingerprint'], $content, true);
    $review = resolve(ReviewLiveShowAction::class);

    // Act / Assert
    expect(fn () => $review->handle($fixture['event'], 2, 2, $data['card']['fingerprint'], []))->toThrow(LiveShowException::class);
    $review->handle($fixture['event'], 2, 2, $data['card']['fingerprint'], [$fixture['key'] => 'Match cancelled']);
    $content = $fixture['run']->refresh()->content;
    expect($content['items'][0]['state'])->toBe('skipped')->and($content['items'][0]['reason'])->toBe('Match cancelled')
        ->and($content['roster'][0]['assigned'])->toBeFalse()->and($content['roster'][0]['arrived'])->not->toBeNull();
});

test('review cannot adopt a source version that changed after the preview', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $pack = $fixture['event']->pack()->firstOrFail();
    resolve(SaveEventPackAction::class)->handle($fixture['event'], 1, $pack->card_fingerprint, [...$pack->content, 'notes' => 'Updated'], false);

    // Act / Assert
    expect(fn () => resolve(ReviewLiveShowAction::class)->handle($fixture['event'], 1, 1, $pack->card_fingerprint, []))->toThrow(RecordChangedException::class);
    expect($fixture['run']->refresh()->version)->toBe(1);
});

test('review refuses a removal reason for a key outside the removed pending items', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $pack = $fixture['event']->pack()->firstOrFail();
    resolve(SaveEventPackAction::class)->handle($fixture['event'], 1, $pack->card_fingerprint, [...$pack->content, 'notes' => 'Revised cues'], false);
    $before = $fixture['run']->refresh()->content;

    // Act / Assert
    expect(fn () => resolve(ReviewLiveShowAction::class)->handle($fixture['event'], 1, 2, $pack->card_fingerprint, ['match:foreign' => 'Cancelled']))->toThrow(LiveShowException::class, __('live-show.unknown_item'));
    expect($fixture['run']->refresh()->version)->toBe(1)->and($fixture['run']->content)->toBe($before);
});

test('review cannot discard a run when its replacement contains only already skipped items', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    resolve(TransitionLiveItemAction::class)->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Skip, 'Cancelled match');
    $pack = $fixture['event']->pack()->firstOrFail();
    resolve(SaveEventPackAction::class)->handle($fixture['event'], 1, $pack->card_fingerprint, [...$pack->content, 'notes' => 'Revised cues'], false);
    $before = $fixture['run']->refresh()->content;

    // Act / Assert
    expect(fn () => resolve(ReviewLiveShowAction::class)->handle($fixture['event'], 2, 2, $pack->card_fingerprint, []))->toThrow(LiveShowException::class, __('live-show.prepare_required'));
    expect($fixture['run']->refresh()->version)->toBe(2)->and($fixture['run']->content)->toBe($before);
});
