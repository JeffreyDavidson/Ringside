<?php

declare(strict_types=1);

use App\Actions\Events\DeleteAction;
use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\SaveEventProductionAction;
use App\Actions\Events\SaveEventPublicationAction;
use App\Actions\Events\UnpublishEventPublicationAction;
use App\Data\Events\EventProductionData;
use App\Data\Events\EventPublicationDraftData;
use App\Data\Events\PublicEventData;
use App\Enums\Events\PublicSection;
use App\Enums\Matches\MatchFinish;
use App\Enums\Promotions\MembershipRole;
use App\Models\Crew\CrewContact;
use App\Models\Events\EventPack;
use App\Models\Events\EventPublication;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Events\PublicEventSourceService;
use App\Services\Promotions\PromotionContextService;
use App\ViewModels\Events\EventPackViewModel;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Assert;

use function Pest\Laravel\get;

test('a released card omits outcomes until results are separately reviewed', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Robin North vs Casey Shore')
        ->assertDontSee('Pinfall')
        ->assertDontSee('View results');
});

test('a released page keeps showing its card with the venue name and address after the venue is deleted', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $venue = Venue::factory()
        ->create(['name' => 'Harbor Arena', 'street_address' => '1 Dock Road', 'city' => 'Portland', 'state' => 'ME', 'zipcode' => '04101']);
    $event->update(['venue_id' => $venue->id]);
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => true,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $venue->delete();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Harbor Arena')
        ->assertSee('1 Dock Road, Portland, ME, 04101')
        ->assertSee('Robin North vs Casey Shore')
        ->assertDontSee(__('public-events.updating'));
});

test('unreleased results have no public page', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertNotFound();
});

test('a card links the separately released results without displaying outcomes', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('View results')
        ->assertDontSee('Pinfall');
});

test('released result reads do not change recorded match results', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $before = DB::table('events_matches')
        ->where('event_id', $event->id)
        ->first();

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Robin North — Pinfall')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect(DB::table('events_matches')
        ->where('event_id', $event->id)
        ->first())->toEqual($before);
});

test('winning finishes name their recorded winning side', function (MatchFinish $finish, string $label): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $fixture['match']->forceFill(['match_finish' => $finish])
        ->save();
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee($label)
        ->assertSee('Robin North — '.$label);

    expect(freshModel($fixture['match'])->match_finish)->toBe($finish);
})->with(['pinfall' => [MatchFinish::Pinfall, 'Pinfall'], 'submission' => [MatchFinish::Submission, 'Submission'], 'disqualification' => [MatchFinish::Disqualification, 'Disqualification'], 'countout' => [MatchFinish::Countout, 'Countout'], 'knockout' => [MatchFinish::Knockout, 'Knockout'], 'stipulation' => [MatchFinish::Stipulation, 'Stipulation'], 'forfeit' => [MatchFinish::Forfeit, 'Forfeit']]);

test('no-winner finishes never name a winning side', function (MatchFinish $finish, string $label): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $fixture['match']->forceFill(['match_finish' => $finish, 'winning_side_id' => null])
        ->save();
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee($label)
        ->assertDontSee('Robin North — '.$label);

    expect(freshModel($fixture['match'])->match_finish)->toBe($finish);
})->with(['time-limit draw' => [MatchFinish::TimeLimitDraw, 'Time Limit Draw'], 'no decision' => [MatchFinish::NoDecision, 'No Decision']]);

test('new drafts and renamed competitors cannot overwrite released facts', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication = app(SaveEventPublicationAction::class)->handle($event, $publication->version, $source['fingerprint'], EventPublicationDraftData::fromValidated([...$publication->draft, 'summary' => 'New draft words']));
    $fixture['left']->update(['name' => 'Private new ring name']);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Welcome to Harbor Night.')
        ->assertDontSee('New draft words')
        ->assertSee('Robin North')
        ->assertDontSee('Private new ring name');
});

test('a newly reviewed card adopts its draft and current competitor name', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication = app(SaveEventPublicationAction::class)->handle($event, $publication->version, $source['fingerprint'], EventPublicationDraftData::fromValidated([...$publication->draft, 'summary' => 'New draft words']));
    $fixture['left']->update(['name' => 'Private new ring name']);
    $source = app(PublicEventSourceService::class)->get($event);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('New draft words')
        ->assertSee('Private new ring name');
});

test('a corrected finish suppresses unreviewed results', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $fixture['match']->update(['match_finish' => MatchFinish::Submission]);

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Results are being updated')
        ->assertDontSee('Pinfall');
});

test('a corrected finish removes the spoiler link while keeping announced facts', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $fixture['match']->update(['match_finish' => MatchFinish::Submission]);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertDontSee('View results')
        ->assertSee('Robin North');
});

test('a deleted match suppresses unreviewed results', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $fixture['match']->delete();

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Results are being updated')
        ->assertDontSee('Pinfall');
});

test('a deleted match removes the spoiler link while keeping announced facts', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $fixture['match']->delete();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertDontSee('View results')
        ->assertSee('Robin North');
});

test('a deleted competitor suppresses unreviewed results', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $fixture['left']->delete();

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Results are being updated')
        ->assertDontSee('Pinfall');
});

test('a deleted competitor removes the spoiler link while keeping announced facts', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $fixture['left']->delete();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertDontSee('View results')
        ->assertSee('Robin North');
});

test('explicitly reviewed corrections display the corrected outcome and date', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $fixture['match']->update(['match_finish' => MatchFinish::Submission]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Submission')
        ->assertSee('Results corrected on');
});

test('changed dates suppress obsolete details without exposing the new schedule', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => 'https://tickets.example.com/harbor', 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $event->update(['date' => now()->addDays(6)]);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Event details are being updated')
        ->assertDontSee('tickets.example.com')
        ->assertDontSee('Casey Shore')
        ->assertDontSee('Harbor Wrestling');
});

test('unpublished results have no public result page', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    app(UnpublishEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertNotFound();
});

test('unpublishing results leaves the public card readable', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    app(UnpublishEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Robin North vs Casey Shore');
});

test('deleting and restoring an event cannot restore its public release', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    app(DeleteAction::class)->handle($event);
    $event->restore();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertNotFound();

    expect(freshModel($publication)->published_at)->toBeNull();
});

test('foreign signed-in visitors can read the public release', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    auth()->logout();
    session()->flush();
    $foreign = Promotion::factory()
        ->create();
    actingAsPromotionMember($foreign, MembershipRole::Owner);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Robin North vs Casey Shore');
});

test('a draw never names a winner despite a historical side reference', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $fixture['match']->update(['match_finish' => MatchFinish::TimeLimitDraw]);
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Time Limit Draw')
        ->assertDontSee('Robin North — Time Limit Draw');
});

test('public projections expose only permitted fields', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertDontSee('LEAK-EVENT-7Q')
        ->assertDontSee('LEAK-MATCH-7Q')
        ->assertDontSee('event_id')
        ->assertDontSee('winning_side_id');

    $page = $response->viewData('page');
    Assert::assertInstanceOf(PublicEventData::class, $page);
    expect(array_keys(get_object_vars($page)))->toBe([
        'title',
        'promotion',
        'announcement',
        'summary',
        'notice',
        'date',
        'timezone',
        'venue',
        'address',
        'doors',
        'tickets',
        'posterUrl',
        'posterAlt',
        'posterCaption',
        'rows',
        'cardUrl',
        'resultsUrl',
        'isResults',
        'isPreview',
        'scheduleChanged',
        'resultsChanged',
        'correctedAt',
    ])
        ->and(array_keys($page->rows[0]))
        ->toBe(['label', 'details', 'outcome']);
});

test('public card and results receive the correct released projection and headers', function (bool $isResults, string $robots): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    auth()->logout();

    // Act
    $response = get(publicShowUrl($publication, $isResults));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertViewHas('page', fn (PublicEventData $page): bool => $page->title === 'Harbor Night' && $page->isResults === $isResults && ! $page->isPreview)
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Robots-Tag', $robots);
})->with(['card' => [false, 'index, follow'], 'results' => [true, 'noindex, nofollow']]);

test('public pages conceal a unknown address', function (string $routeName): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $publicId = '00000000-0000-4000-8000-000000000000';
    $slug = $publication->slug;

    // Act
    $response = get(route($routeName, ['publicId' => $publicId, 'slug' => $slug]));

    // Assert
    $response
        ->assertNotFound();
})->with(['card' => 'shows.show', 'results' => 'shows.results']);

test('public pages conceal a wrong slug', function (string $routeName): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $publicId = $publication->public_id;
    $slug = 'wrong-slug';

    // Act
    $response = get(route($routeName, ['publicId' => $publicId, 'slug' => $slug]));

    // Assert
    $response
        ->assertNotFound();
})->with(['card' => 'shows.show', 'results' => 'shows.results']);

test('a saved unreleased draft has no public card', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertNotFound();
});

test('outward public channels omit operational prose and unselected outcomes', function (bool $isResults, string $heading): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    EventPack::factory()
        ->for($event)
        ->create(['content' => [
            'timezone' => 'UTC',
            'doors_at' => null,
            'revision' => null,
            'master' => 'LEAK-MASTER-7Q',
            'notes' => 'LEAK-FEE-1234.56',
            'follow_up' => 'LEAK-TRAVEL-7Q',
            'matches' => [],
            'segments' => [],
        ]]);
    [$unselected, $opponent] = Wrestler::factory()
        ->for($event->promotion()
            ->firstOrFail())
        ->bookable()
        ->count(2)
        ->sequence(['name' => 'UNSELECTED-7Q'], ['name' => 'Harbor Opponent'])
        ->create()
        ->all();
    EventMatch::factory()
        ->for($event)
        ->withCompetitors([$unselected, $opponent])
        ->create([
            'match_number' => 9,
            'match_finish' => MatchFinish::Submission,
            'preview' => 'LEAK-STORY-7Q',
        ]);
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    auth()->logout();
    app(PromotionContextService::class)->clear();

    // Act
    $response = get(publicShowUrl($publication, $isResults));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee($heading)
        ->assertDontSee('LEAK-EVENT-7Q')
        ->assertDontSee('LEAK-MATCH-7Q')
        ->assertDontSee('LEAK-MASTER-7Q')
        ->assertDontSee('LEAK-FEE-1234.56')
        ->assertDontSee('LEAK-TRAVEL-7Q')
        ->assertDontSee('UNSELECTED-7Q')
        ->assertDontSee('LEAK-STORY-7Q')
        ->assertDontSee('Submission');
})->with(['card' => [false, 'Harbor Night'], 'results' => [true, 'Selected results']]);

test('published event cards and results omit crew contacts and production cues', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $contact = CrewContact::factory()->create(['promotion_id' => $event->promotion_id,
        'name' => 'PRIVATE-CREW-7Q', 'phone' => 'PRIVATE-PHONE-7Q',
        'email' => 'private-crew-7q@example.test', 'notes' => 'PRIVATE-CONTACT-NOTE-7Q']);
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventProductionAction::class)->handle($event, 0, $data['card']['fingerprint'], EventProductionData::fromValidated([
        'notes' => 'PRIVATE-NOTE-7Q',
        'assignments' => [['id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051001', 'contact_id' => $contact->id, 'role' => 'sound', 'timezone' => 'UTC']],
        'cues' => [['id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051003', 'item_key' => 'match:'.$world['match']->id,
            'subject' => 'PRIVATE-SUBJECT-7Q', 'trigger' => 'PRIVATE-TRIGGER-7Q', 'instruction' => 'PRIVATE-CUE-7Q',
            'owner_id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051001']],
    ]), false, administrator());

    // Act
    savePublicDraft($event);
    releasePublicPage($event);
    $publication = releasePublicPage($event, 'results');
    $surfaces = publicEventSurfaces($event, $publication);

    // Assert
    $surfaces['snapshots'] = json_encode([$publication->card, $publication->results], JSON_THROW_ON_ERROR);
    expect($surfaces)->each->not->toContain('PRIVATE-CREW-7Q', 'PRIVATE-PHONE-7Q', 'private-crew-7q@example.test', 'PRIVATE-CONTACT-NOTE-7Q', 'PRIVATE-NOTE-7Q', 'PRIVATE-SUBJECT-7Q', 'PRIVATE-TRIGGER-7Q', 'PRIVATE-CUE-7Q')
        ->and($surfaces['card'])->toContain('Robin North', 'Casey Shore')
        ->and($surfaces['results'])->toContain('Robin North');
});
