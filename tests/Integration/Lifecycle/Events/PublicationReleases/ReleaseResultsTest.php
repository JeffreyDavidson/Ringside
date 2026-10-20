<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Exceptions\Events\EventPublicationException;
use App\Lifecycle\Events\PublicationReleases\PublicationDraftRequirements;
use App\Lifecycle\Events\PublicationReleases\ReleaseResults;
use App\Services\Events\PublicEventCardService;
use App\Services\Events\PublicEventSourceService;
use JMac\Testing\Double;

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

test('it refuses results the published card or the event does not allow', function (string $condition): void {
    // Arrange
    $released = releasedPublicationWorld();
    $publication = $released['publication'];
    $world = $released['world'];
    $event = $world['event'];
    $source = resolve(PublicEventSourceService::class)->get($event);
    if ($condition === 'unpublished card') {
        $publication->published_at = null;
    }
    if ($condition === 'missing card') {
        $publication->card = null;
    }
    if ($condition === 'future event') {
        $event->date = now()->addWeek();
    }
    if ($condition === 'notice') {
        $publication->draft = [...$publication->draft, 'announcement' => 'cancelled', 'notice' => 'Cancelled event'];
        $event->date = now()->addWeek();
    }
    if ($condition === 'no selected results') {
        $publication->draft = [...$publication->draft, 'results' => []];
    }
    if ($condition === 'result outside the card') {
        $publication->draft = [...$publication->draft, 'results' => [$world['match']->id + 1000]];
    }
    if ($condition === 'changed schedule') {
        $publication->schedule_fingerprint = 'earlier-schedule';
    }
    if ($condition === 'changed card') {
        $publication->card_fingerprint = 'earlier-card';
    }

    // Act
    $release = fn () => resolve(ReleaseResults::class)->apply($publication, $event, $world['actor'], $source);

    // Assert
    expect($release)->toThrow(EventPublicationException::class, __('public-events.invalid'))
        ->and($publication->results)->toBeNull();
})->with(['unpublished card', 'missing card', 'future event', 'notice', 'no selected results', 'result outside the card', 'changed schedule', 'changed card']);

test('it refuses a result projection that is incomplete or unrecorded', function (Closure $project): void {
    // Arrange
    $released = releasedPublicationWorld();
    $publication = $released['publication'];
    $world = $released['world'];
    $source = resolve(PublicEventSourceService::class)->get($world['event']);
    $cards = Double::for(PublicEventCardService::class);
    $cards->expects('cardFingerprint')->returns((string) $publication->card_fingerprint);
    $cards->expects('resultSource')->returns($project($source['matches'][0]));
    $release = new ReleaseResults(resolve(PublicationDraftRequirements::class), $cards);

    // Act
    $apply = fn () => $release->apply($publication, $world['event'], $world['actor'], $source);

    // Assert
    expect($apply)->toThrow(EventPublicationException::class, __('public-events.invalid'))
        ->and($publication->results)->toBeNull();
    $cards->verify();
})->with([
    'missing match' => [fn (array $match): array => []],
    'unrecorded finish' => [fn (array $match): array => [[...$match, 'finish' => null]]],
    'unlabelled match' => [fn (array $match): array => [[...$match, 'label' => '']]],
    'unrecorded winner' => [fn (array $match): array => [[...$match, 'winner' => '']]],
]);

test('it refuses results of a match whose finish was cleared after the card was released', function (): void {
    // Arrange
    $released = releasedPublicationWorld();
    $world = $released['world'];
    $world['match']->update(['match_finish' => null]);
    $source = resolve(PublicEventSourceService::class)->get($world['event']);

    // Act
    $release = fn () => resolve(ReleaseResults::class)->apply($released['publication'], $world['event'], $world['actor'], $source);

    // Assert
    expect($release)->toThrow(EventPublicationException::class, __('public-events.invalid'));
});
