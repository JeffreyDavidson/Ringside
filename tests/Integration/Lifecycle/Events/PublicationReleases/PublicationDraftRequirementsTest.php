<?php

declare(strict_types=1);

use App\Enums\Events\PublicAnnouncement;
use App\Exceptions\Events\EventPublicationException;
use App\Lifecycle\Events\PublicationReleases\PublicationDraftRequirements;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Services\Events\PublicEventSourceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function noticeDraft(Event $event): EventPublication
{
    $publication = savePublicDraft($event);
    $publication->draft = [...$publication->draft, 'announcement' => 'cancelled', 'notice' => 'Cancelled event'];

    return $publication;
}

test('it returns the announcement of a draft that can be released', function (): void {
    // Arrange
    $world = publicEventFixture();
    $publication = savePublicDraft($world['event']);
    $source = resolve(PublicEventSourceService::class)->get($world['event']);

    // Act
    $announcement = resolve(PublicationDraftRequirements::class)->ensureDraftReleasable($publication, $world['event'], $source);

    // Assert
    expect($announcement)->toBe(PublicAnnouncement::Scheduled);
});

test('it accepts a notice for an undated or future event and a draft whose poster is still stored', function (Closure $prepare, PublicAnnouncement $expected): void {
    // Arrange
    Storage::fake('local');
    $world = publicEventFixture();
    $publication = $prepare($world);
    $source = resolve(PublicEventSourceService::class)->get($world['event']);

    // Act
    $announcement = resolve(PublicationDraftRequirements::class)->ensureDraftReleasable($publication, $world['event'], $source);

    // Assert
    expect($announcement)->toBe($expected);
})->with([
    'future notice' => [function (array $world): EventPublication {
        $world['event']->date = now()->addWeek();

        return noticeDraft($world['event']);
    }, PublicAnnouncement::Cancelled],
    'undated notice' => [function (array $world): EventPublication {
        $world['event']->date = null;

        return noticeDraft($world['event']);
    }, PublicAnnouncement::Cancelled],
    'stored poster' => [fn (array $world): EventPublication => savePublicDraft($world['event'], ['poster' => UploadedFile::fake()->image('poster.png'), 'poster_alt' => 'Event poster']), PublicAnnouncement::Scheduled],
]);

test('it refuses a draft that cannot be released', function (Closure $prepare): void {
    // Arrange
    Storage::fake('local');
    $world = publicEventFixture();
    $publication = $prepare($world);
    $source = resolve(PublicEventSourceService::class)->get($world['event']);

    // Act
    $ensure = fn () => resolve(PublicationDraftRequirements::class)->ensureDraftReleasable($publication, $world['event'], $source);

    // Assert
    expect($ensure)->toThrow(EventPublicationException::class, __('public-events.invalid'));
})->with([
    'unsaved publication' => [function (array $world): EventPublication {
        $publication = new EventPublication;
        $publication->draft = savePublicDraft($world['event'])->draft;

        return $publication;
    }],
    'removed match' => [function (array $world): EventPublication {
        $publication = savePublicDraft($world['event']);
        $world['match']->delete();

        return $publication;
    }],
    'undated event' => [function (array $world): EventPublication {
        $publication = savePublicDraft($world['event']);
        $world['event']->date = null;

        return $publication;
    }],
    'past notice' => [fn (array $world): EventPublication => noticeDraft($world['event'])],
    'notice without text' => [function (array $world): EventPublication {
        $publication = noticeDraft($world['event']);
        $publication->draft = [...$publication->draft, 'notice' => null];
        $world['event']->date = now()->addWeek();

        return $publication;
    }],
    'missing poster' => [function (array $world): EventPublication {
        $publication = savePublicDraft($world['event'], ['poster' => UploadedFile::fake()->image('poster.png'), 'poster_alt' => 'Event poster']);
        Storage::disk('local')->delete('event-posters/'.publicPosterKey($publication));

        return $publication;
    }],
]);
