<?php

declare(strict_types=1);
use App\Actions\Events\LockEventPublicationAction;
use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\SaveEventPublicationAction;
use App\Actions\Events\UnpublishEventPublicationAction;
use App\Data\Events\EventPublicationDraftData;
use App\Enums\Events\PublicSection;
use App\Exceptions\Events\EventPublicationException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\EventPublication;
use App\Services\Events\PublicEventCardService;
use App\Services\Events\PublicEventSourceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JMac\Testing\Double;

use function Pest\Laravel\get;

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

test('an incomplete result projection fails closed before a snapshot is saved', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    $publication = releasePublicPage($event);
    $source = app(PublicEventSourceService::class)->get($event);
    $query = Double::for(PublicEventCardService::class);
    $query->expects('cardFingerprint')->returns($publication->card_fingerprint);
    $query->expects('resultSource')->returns([]);
    $action = new ReleaseEventPublicationAction(app(LockEventPublicationAction::class), $query);

    // Act / Assert
    expect(fn () => $action->handle($event, $world['actor'], $publication->version, $source['fingerprint'], PublicSection::Results))
        ->toThrow(EventPublicationException::class)
        ->and(freshModel($publication)->results_published_at)->toBeNull()
        ->and(freshModel($publication)->results)->toBeNull();
    $query->verify();
});
