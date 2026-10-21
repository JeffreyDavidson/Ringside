<?php

declare(strict_types=1);

use App\Exceptions\Events\EventPublicationException;
use App\Lifecycle\Events\PublicationReleases\ReleaseCard;
use App\Services\Events\PublicEventSourceService;

use function Pest\Laravel\travelTo;

test('it fills the publication with the released card without saving it', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event);
    $publication->slug = 'placeholder';
    $source = resolve(PublicEventSourceService::class)->get($event);

    // Act
    resolve(ReleaseCard::class)->apply($publication, $event, $world['actor'], $source);

    // Assert
    expect($publication->slug)->toBe('harbor-night')
        ->and($publication->card['title'] ?? null)->toBe('Harbor Night')
        ->and($publication->card_source)->toBe($publication->draft['matches'])
        ->and($publication->schedule_fingerprint)->toBe($source['schedule'])
        ->and($publication->card_fingerprint)->not->toBeNull()
        ->and($publication->published_at?->toIso8601String())->toBe('2026-03-10T12:00:00+00:00')
        ->and($publication->published_by)->toBe($world['actor']->id)
        ->and($publication->unpublished_at)->toBeNull()
        ->and(freshModel($publication)->card)->toBeNull()
        ->and(freshModel($publication)->version)->toBe($publication->version);
});

test('it keeps the slug of a card that was released before', function (): void {
    // Arrange
    $released = releasedPublicationWorld();
    $event = $released['world']['event'];
    $publication = $released['publication'];
    $event->update(['name' => 'Renamed event']);
    $source = resolve(PublicEventSourceService::class)->get($event);

    // Act
    resolve(ReleaseCard::class)->apply($publication, $event, $released['world']['actor'], $source);

    // Assert
    expect($publication->slug)->toBe('harbor-night')
        ->and($publication->card['title'] ?? null)->toBe('Renamed event');
});

test('it republishes an unpublished card', function (): void {
    // Arrange
    $released = releasedPublicationWorld();
    $publication = $released['publication'];
    $publication->unpublished_at = now();
    $source = resolve(PublicEventSourceService::class)->get($released['world']['event']);

    // Act
    resolve(ReleaseCard::class)->apply($publication, $released['world']['event'], $released['world']['actor'], $source);

    // Assert
    expect($publication->unpublished_at)->toBeNull();
});

test('only a notice takes the released results offline', function (string $announcement, ?string $notice, bool $resultsStay): void {
    // Arrange
    $released = releasedPublicationWorld(withResults: true);
    $event = $released['world']['event'];
    $publication = $released['publication'];
    $publication->draft = [...$publication->draft, 'announcement' => $announcement, 'notice' => $notice];
    $event->date = now()->addWeek();
    $source = resolve(PublicEventSourceService::class)->get($event);

    // Act
    resolve(ReleaseCard::class)->apply($publication, $event, $released['world']['actor'], $source);

    // Assert
    expect($publication->results_published_at === null)->toBe(! $resultsStay);
})->with([
    'scheduled' => ['scheduled', null, true],
    'postponed' => ['postponed', 'Moved to next week', false],
]);

test('it refuses a card with a match that has no label', function (): void {
    // Arrange
    $world = publicEventFixture();
    $publication = savePublicDraft($world['event']);
    $world['left']->delete();
    $source = resolve(PublicEventSourceService::class)->get($world['event']);

    // Act
    $release = fn () => resolve(ReleaseCard::class)->apply($publication, $world['event'], $world['actor'], $source);

    // Assert
    expect($release)->toThrow(EventPublicationException::class, __('public-events.invalid'))
        ->and($publication->card)->toBeNull();
});
