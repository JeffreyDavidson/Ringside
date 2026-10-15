<?php

declare(strict_types=1);

use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\SaveEventPublicationAction;
use App\Data\Events\PublicEventData;
use App\Enums\Events\PublicSection;
use App\Enums\Matches\MatchFinish;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Models\Events\EventPublication;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\PublicEventSourceQuery;

use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

describe('edit', function (): void {
    test('owners and managers receive the publication editor payload', function (MembershipRole $role): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion)
            ->scheduled()
            ->create(['name' => 'Harbor Night']);

        actingAsPromotionMember($promotion, $role);

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.publication')
            ->assertViewHas('event', $event)
            ->assertViewHas('publication', null)
            ->assertViewHas('needsReview', false)
            ->assertViewHas('draft', fn (array $draft): bool => $draft['announcement'] === 'scheduled' && $draft['matches'] === [])
            ->assertViewHas('source', fn (array $source): bool => $source['title'] === 'Harbor Night');
    })->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

    test('an existing editor draft is returned without changing its publication', function (): void {
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
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.publication')
            ->assertViewHas('event', $event)
            ->assertViewHas('publication', $publication)
            ->assertViewHas('draft', $publication->draft)
            ->assertViewHas('needsReview', false);
    });

    test('the editor flags a changed released source for review', function (): void {
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
        $source = app(PublicEventSourceQuery::class)->get($event);
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
        $publication->refresh();
        $event->update(['name' => 'Changed private event name']);

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.publication')
            ->assertViewHas('event', $event)
            ->assertViewHas('needsReview', true)
            ->assertSee('Source changed');
    });

    test('guest cannot edit a private publication', function (): void {
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
        $publication->refresh();
        $original = $publication->getAttributes();
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

        auth()->logout();
        session()->flush();

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertRedirect(route('login'));

        expect($publication->refresh()
            ->getAttributes())->toBe($original);
    });

    test('member cannot edit a private publication', function (): void {
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
        $publication->refresh();
        $original = $publication->getAttributes();
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

        auth()->logout();
        session()->flush();
        actingAsPromotionMember($event->promotion()
            ->firstOrFail(), MembershipRole::Member);

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertForbidden();

        expect($publication->refresh()
            ->getAttributes())->toBe($original);
    });

    test('outsider cannot edit a private publication', function (): void {
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
        $publication->refresh();
        $original = $publication->getAttributes();
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

        auth()->logout();
        session()->flush();
        $foreignPromotion = Promotion::factory()
            ->create();
        actingAsPromotionMember($foreignPromotion, MembershipRole::Owner);

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertNotFound();

        expect($publication->refresh()
            ->getAttributes())->toBe($original);
    });

    test('the private editor flags renamed competitor sources for review', function (): void {
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
        $source = app(PublicEventSourceQuery::class)->get($event);
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
        $publication->refresh();
        $publication = app(SaveEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], [...$publication->draft, 'summary' => 'New draft words']);
        $fixture['left']->update(['name' => 'Private new ring name']);

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.publication')
            ->assertViewHas('needsReview', true)
            ->assertSee('Source changed');
    });

    test('the editor flags a corrected finish result source for review', function (): void {
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
        $source = app(PublicEventSourceQuery::class)->get($event);
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
        $publication->refresh();
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
        $publication->refresh();
        $fixture['match']->update(['match_finish' => MatchFinish::Submission]);

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.publication')
            ->assertViewHas('needsReview', true)
            ->assertSee('Source changed');
    });

    test('the editor flags a deleted match result source for review', function (): void {
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
        $source = app(PublicEventSourceQuery::class)->get($event);
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
        $publication->refresh();
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
        $publication->refresh();
        $fixture['match']->delete();

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.publication')
            ->assertViewHas('needsReview', true)
            ->assertSee('Source changed');
    });

    test('the editor flags a deleted competitor result source for review', function (): void {
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
        $source = app(PublicEventSourceQuery::class)->get($event);
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
        $publication->refresh();
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
        $publication->refresh();
        $fixture['left']->delete();

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.publication')
            ->assertViewHas('needsReview', true)
            ->assertSee('Source changed');
    });

    test('foreign owners cannot edit an already released private publication', function (): void {
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
        $source = app(PublicEventSourceQuery::class)->get($event);
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
        $publication->refresh();
        $before = $publication->getAttributes();
        auth()->logout();
        session()->flush();
        $foreign = Promotion::factory()
            ->create();
        actingAsPromotionMember($foreign, MembershipRole::Owner);

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        $response
            ->assertNotFound();

        expect($publication->refresh()
            ->getAttributes())->toBe($before);
    });

});

describe('show', function (): void {
    test('the private preview receives a draft projection without publishing it', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $event->update(['notes' => 'LEAK-EVENT-NOTE-7Q']);
        $publication = EventPublication::factory()
            ->for($event)
            ->create(['draft' => [
                'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
                'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
                'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
                'results' => [$fixture['match']->id],
            ]]);

        // Act
        $response = get(route('events.public-pages.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('public-events.show')
            ->assertViewHas('page', fn (PublicEventData $page): bool => $page->title === 'Harbor Night' && $page->isPreview)
            ->assertSee('Harbor Night')
            ->assertDontSee('LEAK-EVENT-NOTE-7Q')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        expect($publication->refresh()->published_at)->toBeNull();
    });

    test('guest cannot preview a private publication', function (): void {
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
        $publication->refresh();
        $original = $publication->getAttributes();
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

        auth()->logout();
        session()->flush();

        // Act
        $response = get(route('events.public-pages.show', $event));

        // Assert
        $response
            ->assertRedirect(route('login'));

        expect($publication->refresh()
            ->getAttributes())->toBe($original);
    });

    test('member cannot preview a private publication', function (): void {
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
        $publication->refresh();
        $original = $publication->getAttributes();
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

        auth()->logout();
        session()->flush();
        actingAsPromotionMember($event->promotion()
            ->firstOrFail(), MembershipRole::Member);

        // Act
        $response = get(route('events.public-pages.show', $event));

        // Assert
        $response
            ->assertForbidden();

        expect($publication->refresh()
            ->getAttributes())->toBe($original);
    });

    test('outsider cannot preview a private publication', function (): void {
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
        $publication->refresh();
        $original = $publication->getAttributes();
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

        auth()->logout();
        session()->flush();
        $foreignPromotion = Promotion::factory()
            ->create();
        actingAsPromotionMember($foreignPromotion, MembershipRole::Owner);

        // Act
        $response = get(route('events.public-pages.show', $event));

        // Assert
        $response
            ->assertNotFound();

        expect($publication->refresh()
            ->getAttributes())->toBe($original);
    });

    test('foreign owners cannot show an already released private publication', function (): void {
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
        $source = app(PublicEventSourceQuery::class)->get($event);
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
        $publication->refresh();
        $before = $publication->getAttributes();
        auth()->logout();
        session()->flush();
        $foreign = Promotion::factory()
            ->create();
        actingAsPromotionMember($foreign, MembershipRole::Owner);

        // Act
        $response = get(route('events.public-pages.show', $event));

        // Assert
        $response
            ->assertNotFound();

        expect($publication->refresh()
            ->getAttributes())->toBe($before);
    });

    test('the private preview omits operational prose and unselected outcomes', function (): void {
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
        $source = app(PublicEventSourceQuery::class)->get($event);
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
        $publication->refresh();
        app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
        $publication->refresh();

        // Act
        $response = get(route('events.public-pages.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('public-events.show')
            ->assertSee('Harbor Night')
            ->assertDontSee('LEAK-EVENT-7Q')
            ->assertDontSee('LEAK-MATCH-7Q')
            ->assertDontSee('LEAK-MASTER-7Q')
            ->assertDontSee('LEAK-FEE-1234.56')
            ->assertDontSee('LEAK-TRAVEL-7Q')
            ->assertDontSee('UNSELECTED-7Q')
            ->assertDontSee('LEAK-STORY-7Q')
            ->assertDontSee('Submission');
    });

});

describe('update', function (): void {
    test('publication input rejects ticket schemes private hosts credentials and malformed urls', function (string $url): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $source['fingerprint'],
            'announcement' => 'scheduled',
            'tickets' => $url,
        ];

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors('tickets')
            ->assertSessionHasInput('tickets', $url);

        expect(EventPublication::query()
            ->count())->toBe(0);
    })->with([
        'javascript scheme' => 'javascript:alert(1)',
        'insecure HTTP scheme' => 'http://tickets.example.com',
        'protocol-relative URL' => '//tickets.example.com',
        'localhost host' => 'https://localhost/show',
        'IPv4 loopback host' => 'https://127.0.0.1/show',
        'private IPv4 host' => 'https://192.168.1.2/show',
        'IPv6 loopback host' => 'https://[::1]/show',
        'embedded credentials' => 'https://user:secret@tickets.example.com',
        'local network domain' => 'https://arena.local/show',
    ]);

    test('foreign match and result selections are rejected without changing existing draft', function (): void {
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
            ]])
            ->refresh();
        $foreignMatch = EventMatch::factory()
            ->create();
        $source = app(PublicEventSourceQuery::class)->get($event);

        $requestData = [
            'complete' => 1,
            'version' => $publication->version,
            'fingerprint' => $source['fingerprint'],
            'announcement' => 'scheduled',
            'matches' => [[
                'id' => $foreignMatch->id,
                'selected' => 1,
            ]],
            'results' => [$foreignMatch->id],
        ];

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors(['matches.0.id', 'results.0']);

        expect(freshModel($publication)->draft)->toBe($publication->draft);
    });

    test('a malformed offset payload receives validation errors rather than a server error', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $source['fingerprint'],
            'announcement' => 'scheduled',
            'doors_at' => '2026-10-10T19:00',
            'doors_offset' => ['malformed'],
        ];

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors(['doors_offset', 'doors_at']);

        expect(EventPublication::query()
            ->count())->toBe(0);
    });

    test('a truncated multipart form cannot silently save fewer announced matches', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);

        $requestData = [
            'version' => 0,
            'fingerprint' => $source['fingerprint'],
            'announcement' => 'scheduled',
            'matches' => [[
                'id' => $fixture['match']->id,
                'selected' => 1,
            ]],
        ];

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors('complete');

        expect(EventPublication::query()
            ->count())->toBe(0);
    });

    test('invalid doors times do not save a publication', function (string $local, string $offset): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'doors_at' => $local, 'doors_offset' => $offset];

        // Act
        $response = from(route('events.public-pages.edit', $event))->put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors('doors_at')
            ->assertSessionHasInput('doors_at', $local);

        expect(EventPublication::query()
            ->count())->toBe(0);
    })->with(['malformed date' => ['not a date', '-04:00'], 'nonexistent spring-forward time' => ['2026-03-08T02:30', '-05:00'], 'repeated hour without offset' => ['2026-11-01T01:30', ''], 'offset inconsistent with the event timezone' => ['2026-11-01T01:30', '+00:00']]);

    test('valid doors times retain their explicit daylight saving offset', function (string $local, string $offset): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'doors_at' => $local, 'doors_offset' => $offset];

        // Act
        $response = from(route('events.public-pages.edit', $event))->put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('events.public-pages.edit', $event));

        $publication = EventPublication::query()
            ->where('event_id', $event->id)
            ->sole();
        expect($publication->draft['doors_at'])->toBe($local)
            ->and($publication->draft['doors_offset'])
            ->toBe($offset);
    })->with(['repeated hour with daylight offset' => ['2026-11-01T01:30', '-04:00'], 'repeated hour with standard offset' => ['2026-11-01T01:30', '-05:00'], 'ordinary time with matching offset' => ['2026-10-10T19:00', '-04:00']]);

    test('saving a public draft persists it without releasing its card', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'A night at the harbor.', 'matches' => []];

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('events.public-pages.edit', $event))
            ->assertSessionHas('success', __('public-events.saved'));

        $publication = EventPublication::query()
            ->where('event_id', $event->id)
            ->sole();
        expect($publication->draft['summary'])->toBe('A night at the harbor.')
            ->and($publication->card)
            ->toBeNull()
            ->and($publication->published_at)
            ->toBeNull();
    });

    test('a stale draft source cannot create a publication', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);
        $event->update(['name' => 'Changed private event name']);
        $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled'];

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors('publication');

        expect(EventPublication::query()
            ->count())->toBe(0);
    });

    test('guest cannot update a private publication', function (): void {
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
        $publication->refresh();
        $original = $publication->getAttributes();
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

        auth()->logout();
        session()->flush();

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect(route('login'));

        expect($publication->refresh()
            ->getAttributes())->toBe($original);
    });

    test('member cannot update a private publication', function (): void {
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
        $publication->refresh();
        $original = $publication->getAttributes();
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

        auth()->logout();
        session()->flush();
        actingAsPromotionMember($event->promotion()
            ->firstOrFail(), MembershipRole::Member);

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertForbidden();

        expect($publication->refresh()
            ->getAttributes())->toBe($original);
    });

    test('outsider cannot update a private publication', function (): void {
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
        $publication->refresh();
        $original = $publication->getAttributes();
        $source = app(PublicEventSourceQuery::class)->get($event);
        $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

        auth()->logout();
        session()->flush();
        $foreignPromotion = Promotion::factory()
            ->create();
        actingAsPromotionMember($foreignPromotion, MembershipRole::Owner);

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertNotFound();

        expect($publication->refresh()
            ->getAttributes())->toBe($original);
    });

});

describe('validated publication draft', function (): void {
    test('omitted and null selections preserve empty draft defaults', function (array $selections): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $source['fingerprint'],
            'announcement' => 'scheduled',
            ...$selections,
        ];

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect(route('events.public-pages.edit', $event))
            ->assertSessionHasNoErrors();

        $publication = EventPublication::query()
            ->where('event_id', $event->id)
            ->sole();
        expect($publication->draft)->toEqual([
            'announcement' => 'scheduled',
            'summary' => null,
            'notice' => null,
            'address' => false,
            'doors_at' => null,
            'doors_offset' => null,
            'tickets' => null,
            'poster' => null,
            'matches' => [],
            'results' => [],
        ])->and($publication->published_at)
            ->toBeNull();
    })->with([
        'omitted lists' => [[]],
        'null lists' => [
            [
                'matches' => null,
                'results' => null,
            ],
        ],
    ]);

    test('selected match flags and result identities are normalized without editor controls', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $excluded = EventMatch::factory()
            ->for($event)
            ->create();
        $source = app(PublicEventSourceQuery::class)->get($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $source['fingerprint'],
            'announcement' => 'scheduled',
            'summary' => '0',
            'address' => '0',
            'unexpected' => 'Do not store',
            'matches' => [
                [
                    'id' => (string) $fixture['match']->id,
                    'selected' => '1',
                    'type' => '0',
                    'stipulation' => '1',
                ],
                [
                    'id' => (string) $excluded->id,
                    'selected' => '0',
                    'titles' => '1',
                ],
            ],
            'results' => [(string) $fixture['match']->id],
        ];

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect(route('events.public-pages.edit', $event))
            ->assertSessionHasNoErrors();

        $publication = EventPublication::query()
            ->where('event_id', $event->id)
            ->sole();
        expect($publication->draft)->toEqual([
            'announcement' => 'scheduled',
            'summary' => '0',
            'notice' => null,
            'address' => false,
            'doors_at' => null,
            'doors_offset' => null,
            'tickets' => null,
            'poster' => null,
            'matches' => [
                [
                    'id' => $fixture['match']->id,
                    'type' => false,
                    'stipulation' => true,
                    'titles' => false,
                ],
            ],
            'results' => [$fixture['match']->id],
        ])->and($publication->draft['summary'])->toBe('0')
            ->and($publication->draft['address'])->toBeFalse()
            ->and($publication->draft['matches'][0]['id'])->toBe($fixture['match']->id)
            ->and($publication->draft['matches'][0]['type'])->toBeFalse()
            ->and($publication->draft['matches'][0]['stipulation'])->toBeTrue()
            ->and($publication->draft['matches'][0]['titles'])->toBeFalse()
            ->and($publication->draft['results'])->toBe([$fixture['match']->id])
            ->and($publication->published_at)->toBeNull();
    });

    test('changed announcements require a notice before creating a draft', function (string $announcement): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $source['fingerprint'],
            'announcement' => $announcement,
        ];

        // Act
        $response = from(route('events.public-pages.edit', $event))
            ->put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect(route('events.public-pages.edit', $event))
            ->assertSessionHasErrors('notice')
            ->assertSessionHasInput('announcement', $announcement);

        expect(EventPublication::query()
            ->count())->toBe(0);
    })->with(['cancelled', 'postponed', 'rescheduled']);

    test('unknown match selection keys cannot create a draft', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $source['fingerprint'],
            'announcement' => 'scheduled',
            'matches' => [
                [
                    'id' => $fixture['match']->id,
                    'selected' => 1,
                    'unexpected' => 'Do not store',
                ],
            ],
        ];

        // Act
        $response = put(route('events.public-pages.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect()
            ->assertSessionHasErrors('matches.0')
            ->assertSessionHasInput('matches', $requestData['matches']);

        expect(EventPublication::query()
            ->count())->toBe(0);
    });
});
