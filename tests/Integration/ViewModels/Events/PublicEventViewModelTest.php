<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use App\ViewModels\Events\PublicEventViewModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\travelTo;

/**
 * A released page with a poster, so every withheld field has something to withhold.
 *
 * @return array{world: array{event: Event, match: EventMatch, actor: User, left: Wrestler, right: Wrestler}, publication: EventPublication}
 */
function releasedPublicPageWithPoster(): array
{
    Storage::fake('local');
    $world = publicEventFixture();
    $event = $world['event'];
    $event->update(['date' => now()->subDay()]);
    savePublicDraft($event, [
        'summary' => 'Welcome to Harbor Night.',
        'tickets' => 'https://tickets.example.com/show',
        'poster' => UploadedFile::fake()->image('poster.png'),
        'poster_alt' => 'Harbor Night poster',
        'poster_caption' => 'Doors at seven',
    ]);
    releasePublicPage($event);
    $publication = releasePublicPage($event, 'results');

    return ['world' => $world, 'publication' => $publication->load('event')];
}

test('it shows the released card with its poster and a link to the results', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $released = releasedPublicPageWithPoster();
    $publication = $released['publication'];
    $card = $publication->card ?? throw new RuntimeException('Expected a released card');

    // Act
    $page = resolve(PublicEventViewModel::class)->page($publication, $card);

    // Assert
    expect($page->title)->toBe('Harbor Night')
        ->and($page->promotion)->toBe('Harbor Wrestling')
        ->and($page->announcement)->toBe('scheduled')
        ->and($page->summary)->toBe('Welcome to Harbor Night.')
        ->and($page->tickets)->toBe('https://tickets.example.com/show')
        ->and($page->timezone)->toBe('America/New_York')
        ->and($page->posterAlt)->toBe('Harbor Night poster')
        ->and($page->posterCaption)->toBe('Doors at seven')
        ->and($page->posterUrl)->toBe(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $card['poster']['key'] ?? '']))
        ->and($page->rows)->toBe($card['rows'])
        ->and($page->cardUrl)->toBe(publicShowUrl($publication))
        ->and($page->resultsUrl)->toBe(publicShowUrl($publication, true))
        ->and($page->isResults)->toBeFalse()
        ->and($page->isPreview)->toBeFalse()
        ->and($page->scheduleChanged)->toBeFalse()
        ->and($page->resultsChanged)->toBeFalse()
        ->and($page->correctedAt)->toBeNull();
});

test('it shows the released results rows and when they were corrected', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $released = releasedPublicPageWithPoster();
    $world = $released['world'];
    travelTo('2026-03-11 09:30:00');
    $publication = releasePublicPage($world['event'], 'results')->load('event');

    // Act
    $page = resolve(PublicEventViewModel::class)->page($publication, $publication->card ?? throw new RuntimeException('Expected a released card'), results: true);

    // Assert
    expect($page->isResults)->toBeTrue()
        ->and($page->rows)->toBe([['label' => 'Robin North vs Casey Shore', 'details' => '', 'outcome' => 'Robin North — Pinfall']])
        ->and($page->correctedAt)->toBe('2026-03-11T09:30:00+00:00')
        ->and($page->resultsChanged)->toBeFalse();
});

test('it withholds the card details once the schedule has changed', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $released = releasedPublicPageWithPoster();
    $released['world']['event']->update(['date' => now()->subDays(2)]);
    $publication = $released['publication']->refresh()->load('event');

    // Act
    $page = resolve(PublicEventViewModel::class)->page($publication, $publication->card ?? throw new RuntimeException('Expected a released card'), results: true);

    // Assert
    expect($page->title)->toBe('Harbor Night')
        ->and($page->timezone)->toBe('America/New_York')
        ->and($page->promotion)->toBeEmpty()
        ->and($page->announcement)->toBeEmpty()
        ->and($page->summary)->toBeNull()
        ->and($page->notice)->toBeNull()
        ->and($page->date)->toBeNull()
        ->and($page->venue)->toBeNull()
        ->and($page->address)->toBeNull()
        ->and($page->doors)->toBeNull()
        ->and($page->tickets)->toBeNull()
        ->and($page->posterUrl)->toBeNull()
        ->and($page->posterAlt)->toBe('Harbor Night poster')
        ->and($page->posterCaption)->toBeNull()
        ->and($page->rows)->toBeEmpty()
        ->and($page->resultsUrl)->toBeNull()
        ->and($page->correctedAt)->toBeNull()
        ->and($page->scheduleChanged)->toBeTrue();
});

test('it empties the results rows but keeps the card once the results have changed', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $released = releasedPublicPageWithPoster();
    $released['world']['match']->forceFill(['match_finish' => MatchFinish::NoDecision])->save();
    $publication = $released['publication']->refresh()->load('event');
    $card = $publication->card ?? throw new RuntimeException('Expected a released card');

    // Act
    $results = resolve(PublicEventViewModel::class)->page($publication, $card, results: true);
    $cardPage = resolve(PublicEventViewModel::class)->page($publication, $card);

    // Assert
    expect($results->rows)->toBeEmpty()
        ->and($results->resultsChanged)->toBeTrue()
        ->and($results->correctedAt)->toBeNull()
        ->and($results->summary)->toBe('Welcome to Harbor Night.')
        ->and($cardPage->rows)->toBe($card['rows'])
        ->and($cardPage->resultsChanged)->toBeFalse()
        ->and($cardPage->resultsUrl)->toBeNull();
});

test('it previews the draft with the preview poster and no results link', function (): void {
    // Arrange
    travelTo('2026-03-10 12:00:00');
    $released = releasedPublicPageWithPoster();
    $event = $released['world']['event'];
    $publication = $released['publication'];

    // Act
    $preview = resolve(PublicEventViewModel::class)->preview($event, $publication);

    // Assert
    expect($preview->isPreview)->toBeTrue()
        ->and($preview->resultsUrl)->toBeNull()
        ->and($preview->posterUrl)->toBe(route('events.public-pages.poster', $event))
        ->and($preview->cardUrl)->toBe(publicShowUrl($publication))
        ->and($preview->posterCaption)->toBe('Doors at seven')
        ->and($preview->summary)->toBe('Welcome to Harbor Night.');
});

test('it previews a draft without a poster', function (): void {
    // Arrange
    $world = publicEventFixture();
    $publication = savePublicDraft($world['event']);

    // Act
    $preview = resolve(PublicEventViewModel::class)->preview($world['event'], $publication);

    // Assert
    expect($preview->posterUrl)->toBeNull()
        ->and($preview->posterAlt)->toBeNull()
        ->and($preview->posterCaption)->toBeNull();
});
