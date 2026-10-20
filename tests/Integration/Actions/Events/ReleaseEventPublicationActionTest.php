<?php

declare(strict_types=1);
use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\SaveEventPublicationAction;
use App\Actions\Events\UnpublishEventPublicationAction;
use App\Data\Events\EventPublicationDraftData;
use App\Enums\Events\PublicSection;
use App\Exceptions\Events\EventPublicationException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\EventPublication;
use App\Services\Events\PublicEventSourceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

test('stale copies and opposing publication operations cannot overwrite a newer release', function (string $operation): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event);
    $stale = $publication->version;
    $source = app(PublicEventSourceService::class)->get($event);
    releasePublicPage($event);

    $call = match ($operation) {
        'save' => fn () => app(SaveEventPublicationAction::class)->handle($event, $stale, $source['fingerprint'], EventPublicationDraftData::fromValidated($publication->draft)),
        'release' => fn () => app(ReleaseEventPublicationAction::class)->handle($event, $world['actor'], $stale, $source['fingerprint'], PublicSection::Card),
        'unpublish' => fn () => app(UnpublishEventPublicationAction::class)->handle($event, $world['actor'], $stale, $source['fingerprint'], PublicSection::Card),
        default => throw new LogicException('Unknown test operation'),
    };

    expect($call)->toThrow(RecordChangedException::class)
        ->and(freshModel($publication)->version)->toBe($stale + 1)
        ->and(freshModel($publication)->published_at)->not->toBeNull();
})->with(['save', 'release', 'unpublish']);

test('public notices retain the address identity and never cancel the internal event', function (string $announcement): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $event->update(['date' => now()->addWeek()]);
    savePublicDraft($event, ['announcement' => $announcement, 'notice' => 'Public schedule notice', 'tickets' => 'https://tickets.example.com/show']);
    $publication = releasePublicPage($event);
    $url = publicShowUrl($publication);
    $before = freshModel($event)->getAttributes();

    get($url)->assertSee(ucfirst($announcement))->assertSee('Public schedule notice')->assertDontSee('tickets.example.com')->assertDontSee('Robin North');
    expect(freshModel($event)->getAttributes())->toBe($before);
    $event->update(['name' => 'Renamed event']);
    savePublicDraft($event, ['announcement' => 'scheduled', 'notice' => null]);
    releasePublicPage($event);
    get($url)->assertSee('Renamed event');
    expect(freshModel($publication)->slug)->toBe($publication->slug);
})->with(['cancelled', 'postponed']);

test('results reject future events and unannounced or unrecorded matches', function (string $condition): void {
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    releasePublicPage($event);
    if ($condition === 'future') {
        $event->update(['date' => now()->addWeek()]);
        savePublicDraft($event);
        releasePublicPage($event);
    }
    if ($condition === 'unrecorded') {
        $world['match']->update(['match_finish' => null]);
    }
    if ($condition === 'unannounced') {
        savePublicDraft($event, ['matches' => []]);
        releasePublicPage($event);
    }
    $publication = EventPublication::query()->where('event_id', $event->id)->firstOrFail();
    $source = app(PublicEventSourceService::class)->get($event);

    expect(fn () => app(ReleaseEventPublicationAction::class)->handle($event, $world['actor'], $publication->version, $source['fingerprint'], PublicSection::Results))->toThrow(EventPublicationException::class)
        ->and(freshModel($publication)->results_published_at)->toBeNull();
})->with(['future', 'unrecorded', 'unannounced']);

test('publication factory and publisher relations identify only server recorded actors', function (): void {
    $publication = EventPublication::factory()->create();

    expect($publication->event()->firstOrFail()->promotion_id)->toBe($publication->promotion_id);
    $world = publicEventFixture();
    savePublicDraft($world['event']);
    releasePublicPage($world['event']);
    $released = releasePublicPage($world['event'], 'results');
    $released->load(['publisher', 'resultsPublisher']);
    expect(requiredModel($released->publisher)->id)->toBe($world['actor']->id)->and(requiredModel($released->resultsPublisher)->id)->toBe($world['actor']->id);
});

test('card release refuses missing schedule assets and removed announced competitors', function (string $condition): void {
    // Arrange
    Storage::fake('local');
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event, $condition === 'poster' ? ['poster' => UploadedFile::fake()->image('poster.png'), 'poster_alt' => 'Event poster'] : []);
    if ($condition === 'date') {
        $event->update(['date' => null]);
    }
    if ($condition === 'match') {
        $world['match']->delete();
    }
    if ($condition === 'competitor') {
        $world['left']->delete();
    }
    if ($condition === 'past notice') {
        savePublicDraft($event, ['announcement' => 'cancelled', 'notice' => 'Cancelled event']);
        $publication->refresh();
    }
    if ($condition === 'poster') {
        Storage::disk('local')->delete('event-posters/'.publicPosterKey($publication));
    }
    $source = app(PublicEventSourceService::class)->get($event);

    // Act / Assert
    expect(fn () => app(ReleaseEventPublicationAction::class)->handle($event, $world['actor'], $publication->version, $source['fingerprint'], PublicSection::Card))
        ->toThrow(EventPublicationException::class)
        ->and(freshModel($publication)->published_at)->toBeNull();
})->with(['date', 'match', 'competitor', 'past notice', 'poster']);

test('a card release stores the same card, source and fingerprints as before the release classes', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event);
    $source = resolve(PublicEventSourceService::class)->get($event);

    // Act
    resolve(ReleaseEventPublicationAction::class)->handle($event, $world['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);

    // Assert
    $released = freshModel($publication);
    $rows = [['label' => 'Robin North vs Casey Shore', 'details' => 'Singles', 'outcome' => null]];
    $selection = [['id' => $world['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]];
    expect($released->card)->toEqual([
        'title' => 'Harbor Night',
        'promotion' => 'Harbor Wrestling',
        'announcement' => 'scheduled',
        'summary' => 'Welcome to Harbor Night.',
        'notice' => null,
        'date' => $event->date?->toIso8601String(),
        'timezone' => 'America/New_York',
        'venue' => null,
        'address' => null,
        'doors' => null,
        'tickets' => null,
        'poster' => null,
        'rows' => $rows,
    ])
        ->and($released->card_source)->toEqual($selection)
        ->and($released->card_fingerprint)->toBe(hash('sha256', serialize(['Harbor Night', 'Harbor Wrestling', $rows])))
        ->and($released->schedule_fingerprint)->toBe(hash('sha256', serialize([$event->date?->toIso8601String(), $event->venue_id, null, 'America/New_York', null])))
        ->and($released->slug)->toBe('harbor-night')
        ->and($released->published_at?->toIso8601String())->toBe('2026-03-10T12:00:00+00:00')
        ->and($released->published_by)->toBe($world['actor']->id)
        ->and($released->unpublished_at)->toBeNull()
        ->and($released->version)->toBe($publication->version + 1);
});

test('a results release stores the same results, source and fingerprints as before the release classes', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    $publication = releasePublicPage($event);
    $source = resolve(PublicEventSourceService::class)->get($event);
    $card = $publication->card;

    // Act
    resolve(ReleaseEventPublicationAction::class)->handle($event, $world['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);

    // Assert
    $released = freshModel($publication);
    expect($released->results)->toEqual(['rows' => [['label' => 'Robin North vs Casey Shore', 'details' => '', 'outcome' => 'Robin North — Pinfall']], 'corrected_at' => null])
        ->and($released->results_source)->toBe([$world['match']->id])
        ->and($released->results_fingerprint)->toBe(hash('sha256', serialize($source['matches'])))
        ->and($released->results_published_at?->toIso8601String())->toBe('2026-03-10T12:00:00+00:00')
        ->and($released->results_published_by)->toBe($world['actor']->id)
        ->and($released->card)->toEqual($card)
        ->and($released->version)->toBe($publication->version + 1);
});

test('releasing results again marks them corrected at the release time', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    releasePublicPage($event);
    $publication = releasePublicPage($event, 'results');
    travelTo('2026-03-11 09:30:00');
    $source = resolve(PublicEventSourceService::class)->get($event);

    // Act
    resolve(ReleaseEventPublicationAction::class)->handle($event, $world['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);

    // Assert
    expect(freshModel($publication)->results['corrected_at'] ?? null)->toBe('2026-03-11T09:30:00+00:00');
});

test('releasing a notice card clears the released results time', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    releasePublicPage($event);
    releasePublicPage($event, 'results');
    $event->update(['date' => now()->addWeek()]);
    $publication = savePublicDraft($event, ['announcement' => 'postponed', 'notice' => 'Moved']);
    $source = resolve(PublicEventSourceService::class)->get($event);

    // Act
    resolve(ReleaseEventPublicationAction::class)->handle($event, $world['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);

    // Assert
    $released = freshModel($publication);
    expect($released->results_published_at)->toBeNull()
        ->and($released->card['announcement'] ?? null)->toBe('postponed')
        ->and($released->card)->toHaveKey('date', null);
});
