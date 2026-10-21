<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Exceptions\Events\EventPublicationException;
use App\Lifecycle\Events\PublicationReleases\ReleaseResults;
use App\Models\Events\EventPublication;
use App\Models\Matches\EventMatch;
use App\Services\Events\PublicEventSourceService;

use function Pest\Laravel\travelTo;

test('it fills the publication with the released results without saving them', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $released = releasedPublicationWorld();
    $publication = $released['publication'];
    $world = $released['world'];
    $source = resolve(PublicEventSourceService::class)->get($world['event']);

    // Act
    resolve(ReleaseResults::class)->apply($publication, $world['event'], $world['actor'], $source);

    // Assert
    expect($publication->results)->toBe(['rows' => [['label' => 'Robin North vs Casey Shore', 'details' => '', 'outcome' => 'Robin North — Pinfall']], 'corrected_at' => null])
        ->and($publication->results_source)->toBe([$world['match']->id])
        ->and($publication->results_fingerprint)->not->toBeNull()
        ->and($publication->results_published_at?->toIso8601String())->toBe('2026-03-10T12:00:00+00:00')
        ->and($publication->results_published_by)->toBe($world['actor']->id)
        ->and(freshModel($publication)->results)->toBeNull();
});

test('it marks results that were released before as corrected', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $released = releasedPublicationWorld(withResults: true);
    $publication = $released['publication'];
    $world = $released['world'];
    $source = resolve(PublicEventSourceService::class)->get($world['event']);
    travelTo('2026-03-11 09:30:00');

    // Act
    resolve(ReleaseResults::class)->apply($publication, $world['event'], $world['actor'], $source);

    // Assert
    expect($publication->results['corrected_at'] ?? null)->toBe('2026-03-11T09:30:00+00:00');
});

test('it reports a match without a winner by its finish alone', function (): void {
    // Arrange
    $released = releasedPublicationWorld();
    $publication = $released['publication'];
    $world = $released['world'];
    $world['match']->forceFill(['match_finish' => MatchFinish::NoDecision])->save();
    $source = resolve(PublicEventSourceService::class)->get($world['event']);

    // Act
    resolve(ReleaseResults::class)->apply($publication, $world['event'], $world['actor'], $source);

    // Assert
    expect($publication->results['rows'][0]['outcome'] ?? null)->toBe(MatchFinish::NoDecision->label());
});

test('it refuses results the published card or the event does not allow', function (Closure $prepare): void {
    // Arrange
    $released = releasedPublicationWorld();
    $publication = $released['publication'];
    $world = $released['world'];
    $source = resolve(PublicEventSourceService::class)->get($world['event']);
    $prepare($publication, $world);

    // Act
    $release = fn () => resolve(ReleaseResults::class)->apply($publication, $world['event'], $world['actor'], $source);

    // Assert
    expect($release)->toThrow(EventPublicationException::class, __('public-events.invalid'))
        ->and($publication->results)->toBeNull();
})->with([
    'unpublished card' => [function (EventPublication $publication, array $world): void {
        $publication->published_at = null;
    }],
    'missing card' => [function (EventPublication $publication, array $world): void {
        $publication->card = null;
    }],
    'future event' => [function (EventPublication $publication, array $world): void {
        $world['event']->date = now()->addWeek();
    }],
    'notice for a future event' => [function (EventPublication $publication, array $world): void {
        $publication->draft = [...$publication->draft, 'announcement' => 'cancelled', 'notice' => 'Cancelled event'];
        $world['event']->date = now()->addWeek();
    }],
    'notice for an undated event' => [function (EventPublication $publication, array $world): void {
        $publication->draft = [...$publication->draft, 'announcement' => 'cancelled', 'notice' => 'Cancelled event'];
        $world['event']->date = null;
    }],
    'no selected results' => [function (EventPublication $publication, array $world): void {
        $publication->draft = [...$publication->draft, 'results' => []];
    }],
    'result outside the card' => [function (EventPublication $publication, array $world): void {
        $publication->draft = [...$publication->draft, 'results' => [$world['match']->id + 1000]];
    }],
    'changed schedule' => [function (EventPublication $publication, array $world): void {
        $publication->schedule_fingerprint = 'earlier-schedule';
    }],
    'changed card' => [function (EventPublication $publication, array $world): void {
        $publication->card_fingerprint = 'earlier-card';
    }],
]);

test('it refuses a match that is not recorded', function (Closure $prepare): void {
    // Arrange
    $released = releasedPublicationWorld();
    $world = $released['world'];
    $prepare($world['match']);
    $source = resolve(PublicEventSourceService::class)->get($world['event']);

    // Act
    $release = fn () => resolve(ReleaseResults::class)->apply($released['publication'], $world['event'], $world['actor'], $source);

    // Assert
    expect($release)->toThrow(EventPublicationException::class, __('public-events.invalid'))
        ->and($released['publication']->results)->toBeNull();
})->with([
    'cleared finish' => [fn (EventMatch $match) => $match->update(['match_finish' => null])],
    'finish needing a winner without one' => [fn (EventMatch $match) => $match->update(['winning_side_id' => null])],
]);
