<?php

declare(strict_types=1);

use App\Enums\Events\PublicAnnouncement;
use App\Exceptions\Events\EventPublicationException;
use App\Lifecycle\Events\PublicationReleases\PublicationDraftRequirements;
use App\Models\Events\EventPublication;
use App\Services\Events\PublicEventSourceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('it accepts a notice for an undated or future event and a draft whose poster is still stored', function (string $case): void {
    // Arrange
    Storage::fake('local');
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event, $case === 'poster' ? ['poster' => UploadedFile::fake()->image('poster.png'), 'poster_alt' => 'Event poster'] : []);
    if ($case !== 'poster') {
        $publication->draft = [...$publication->draft, 'announcement' => 'cancelled', 'notice' => 'Cancelled event'];
        $event->date = $case === 'future notice' ? now()->addWeek() : null;
    }
    $source = resolve(PublicEventSourceService::class)->get($event);

    // Act
    $announcement = resolve(PublicationDraftRequirements::class)->ensureDraftReleasable($publication, $event, $source);

    // Assert
    expect($announcement)->toBe($case === 'poster' ? PublicAnnouncement::Scheduled : PublicAnnouncement::Cancelled);
})->with(['future notice', 'undated notice', 'poster']);

test('it refuses a draft that cannot be released', function (string $condition): void {
    // Arrange
    Storage::fake('local');
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event, $condition === 'missing poster' ? ['poster' => UploadedFile::fake()->image('poster.png'), 'poster_alt' => 'Event poster'] : []);
    if ($condition === 'unsaved publication') {
        $publication = new EventPublication;
        $publication->draft = savePublicDraft($event)->draft;
    }
    if ($condition === 'removed match') {
        $world['match']->delete();
    }
    if ($condition === 'undated event') {
        $event->date = null;
    }
    if ($condition === 'past notice') {
        $publication->draft = [...$publication->draft, 'announcement' => 'cancelled', 'notice' => 'Cancelled event'];
    }
    if ($condition === 'notice without text') {
        $publication->draft = [...$publication->draft, 'announcement' => 'cancelled', 'notice' => null];
        $event->date = now()->addWeek();
    }
    if ($condition === 'missing poster') {
        Storage::disk('local')->delete('event-posters/'.publicPosterKey($publication));
    }
    $source = resolve(PublicEventSourceService::class)->get($event);

    // Act
    $ensure = fn () => resolve(PublicationDraftRequirements::class)->ensureDraftReleasable($publication, $event, $source);

    // Assert
    expect($ensure)->toThrow(EventPublicationException::class, __('public-events.invalid'));
})->with(['unsaved publication', 'removed match', 'undated event', 'past notice', 'notice without text', 'missing poster']);
