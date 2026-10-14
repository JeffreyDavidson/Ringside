<?php

declare(strict_types=1);

use App\Data\Events\PublicEventData;
use App\Enums\Promotions\MembershipRole;
use App\Http\Controllers\Events\PublicEventController;
use App\Http\Controllers\Events\ReleaseEventPublicationController;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Queries\Events\PublicEventSourceQuery;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

describe('show', function (): void {
    test('public projections expose only the explicitly permitted page and row properties', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        savePublicDraft($fixture['event']);
        $publication = releasePublicPage($fixture['event']);

        // Act
        $response = get(publicShowUrl($publication));

        // Assert
        $response
            ->assertDontSee('LEAK-EVENT-7Q')
            ->assertDontSee('LEAK-MATCH-7Q')
            ->assertDontSee('event_id')
            ->assertDontSee('winning_side_id');

        $page = $response->viewData('page');

        if (! $page instanceof PublicEventData) {
            throw new RuntimeException('Public projection missing');
        }
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
            ->and(array_keys($page->rows[0]))->toBe(['label', 'details', 'outcome']);
    });
});

describe('edit', function (): void {
    test('members cannot prepare public pages while owners and managers can', function (MembershipRole $role, bool $allowed): void {
        // Arrange
        $event = Event::factory()->for(Promotion::factory())->scheduled()->create();
        actingAsPromotionMember($event->promotion()->firstOrFail(), $role);

        // Act
        $response = get(route('events.public-pages.edit', $event));

        // Assert
        if ($allowed) {
            $response->assertOk();

            return;
        }
        $response->assertForbidden();
    })->with([
        'owner' => [MembershipRole::Owner, true],
        'manager' => [MembershipRole::Manager, true],
        'member' => [MembershipRole::Member, false],
    ]);
});

describe('update', function (): void {
    test('publication input rejects ticket schemes private hosts credentials and malformed urls', function (string $url): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $editorResponse = get(route('events.public-pages.edit', $event));

        // Act
        $response = put(route('events.public-pages.update', $event), [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => data_get($editorResponse->viewData('source'), 'fingerprint'),
            'announcement' => 'scheduled',
            'tickets' => $url,
        ]);

        // Assert
        $response
            ->assertSessionHasErrors('tickets')
            ->assertSessionHasInput('tickets', $url);

        expect(EventPublication::query()->count())->toBe(0);
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

    test('doors time requires an explicit valid offset through daylight saving transitions', function (string $local, string $offset, bool $valid): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $editorResponse = get(route('events.public-pages.edit', $event));

        // Act
        $response = put(route('events.public-pages.update', $event), [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => data_get($editorResponse->viewData('source'), 'fingerprint'),
            'announcement' => 'scheduled',
            'doors_at' => $local,
            'doors_offset' => $offset,
        ]);

        // Assert
        if ($valid) {
            $response->assertSessionHasNoErrors();

            return;
        }
        $response->assertSessionHasErrors('doors_at');

        expect(EventPublication::query()->count())->toBe(0);
    })->with([
        'malformed date' => ['not a date', '-04:00', false],
        'nonexistent spring-forward time' => ['2026-03-08T02:30', '-05:00', false],
        'repeated hour without offset' => ['2026-11-01T01:30', '', false],
        'repeated hour with daylight offset' => ['2026-11-01T01:30', '-04:00', true],
        'repeated hour with standard offset' => ['2026-11-01T01:30', '-05:00', true],
        'offset inconsistent with the event timezone' => ['2026-11-01T01:30', '+00:00', false],
        'ordinary time with matching offset' => ['2026-10-10T19:00', '-04:00', true],
    ]);

    test('foreign match and result selections are rejected without changing existing draft', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $publication = savePublicDraft($event);
        $foreignMatch = EventMatch::factory()->create();
        $editorResponse = get(route('events.public-pages.edit', $event));

        // Act
        $response = put(route('events.public-pages.update', $event), [
            'complete' => 1,
            'version' => $publication->version,
            'fingerprint' => data_get($editorResponse->viewData('source'), 'fingerprint'),
            'announcement' => 'scheduled',
            'matches' => [[
                'id' => $foreignMatch->id,
                'selected' => 1,
            ]],
            'results' => [$foreignMatch->id],
        ]);

        // Assert
        $response->assertSessionHasErrors(['matches.0.id', 'results.0']);

        expect(freshModel($publication)->draft)->toBe($publication->draft);
    });

    test('a stale source cannot save and a release requires deliberate review', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $editorResponse = get(route('events.public-pages.edit', $event));
        $event->update(['name' => 'Changed private event name']);

        // Act
        $response = put(route('events.public-pages.update', $event), [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => data_get($editorResponse->viewData('source'), 'fingerprint'),
            'announcement' => 'scheduled',
        ]);

        // Assert
        $response->assertSessionHasErrors('publication');

        expect(EventPublication::query()->count())->toBe(0);

        // Arrange
        $publication = savePublicDraft($event);
        $source = app(PublicEventSourceQuery::class)->get($event);

        // Act
        $response = post(route('events.public-pages.publish', $event), [
            'complete' => 1,
            'version' => $publication->version,
            'fingerprint' => $source['fingerprint'],
            'section' => 'card',
        ]);

        // Assert
        $response->assertSessionHasErrors('reviewed');

        expect(freshModel($publication)->card)->toBeNull();
    });

    test('a malformed offset payload receives validation errors rather than a server error', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);

        // Act
        $response = put(route('events.public-pages.update', $event), [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $source['fingerprint'],
            'announcement' => 'scheduled',
            'doors_at' => '2026-10-10T19:00',
            'doors_offset' => ['malformed'],
        ]);

        // Assert
        $response->assertSessionHasErrors(['doors_offset', 'doors_at']);
    });

    test('a truncated multipart form cannot silently save fewer announced matches', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $source = app(PublicEventSourceQuery::class)->get($event);

        // Act
        $response = put(route('events.public-pages.update', $event), [
            'version' => 0,
            'fingerprint' => $source['fingerprint'],
            'announcement' => 'scheduled',
            'matches' => [[
                'id' => $fixture['match']->id,
                'selected' => 1,
            ]],
        ]);

        // Assert
        $response->assertSessionHasErrors('complete');

        expect(EventPublication::query()->count())->toBe(0);
    });
});

describe('publication workflow', function (): void {
    test('a saved public draft remains private until explicitly reviewed and published', function (): void {
        // Arrange
        $event = Event::factory()->for(Promotion::factory())->scheduled()->create([
            'name' => 'Harbor Night',
            'preview' => 'LEAK-EVENT-NOTE-7Q',
        ]);

        actingAs(administrator());

        // Act
        $editorResponse = get(route('events.public-pages.edit', $event));

        // Assert
        $editorResponse
            ->assertOk()
            ->assertViewIs('events.publication')
            ->assertViewHas('event', $event)
            ->assertViewHas('publication', null)
            ->assertViewHas('needsReview', false)
            ->assertViewHas('draft', fn (array $draft): bool => $draft['announcement'] === 'scheduled' && $draft['matches'] === [])
            ->assertViewHas('source', fn (array $source): bool => $source['title'] === 'Harbor Night');

        // Act
        $response = put(route('events.public-pages.update', $event), [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => data_get($editorResponse->viewData('source'), 'fingerprint'),
            'announcement' => 'scheduled',
            'summary' => 'A night at the harbor.',
            'matches' => [],
        ]);

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('events.public-pages.edit', $event))
            ->assertSessionHas('success', __('public-events.saved'));

        $publication = EventPublication::query()->where('event_id', $event->id)->firstOrFail();
        expect($publication->card)->toBeNull();

        // Act
        $response = get(route('shows.show', [
            'publicId' => $publication->public_id,
            'slug' => $publication->slug,
        ]));

        // Assert
        $response->assertNotFound();

        // Act
        $previewResponse = get(route('events.public-pages.show', $event));

        // Assert
        $previewResponse
            ->assertOk()
            ->assertViewIs('public-events.show')
            ->assertViewHas('page', fn (PublicEventData $page): bool => $page->title === 'Harbor Night' && $page->isPreview)
            ->assertSee('Harbor Night')
            ->assertDontSee('LEAK-EVENT-NOTE-7Q')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // Act
        $response = post(route('events.public-pages.publish', $event), [
            'complete' => 1,
            'version' => 1,
            'fingerprint' => data_get($editorResponse->viewData('source'), 'fingerprint'),
            'reviewed' => 1,
            'section' => 'card',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();

        // Arrange
        auth()->logout();

        // Act
        $response = get(route('shows.show', [
            'publicId' => $publication->public_id,
            'slug' => $publication->slug,
        ]));

        // Assert
        $response
            ->assertSee('A night at the harbor.')
            ->assertDontSee('LEAK-EVENT-NOTE-7Q');
    })->see(PublicEventController::class, ReleaseEventPublicationController::class);
});

describe('authorization', function (): void {
    test('private publication actions refuse guests members and outsiders', function (string $operation, string $actor): void {
        // Arrange
        $fixture = publicEventFixture();
        $event = $fixture['event'];
        $publication = savePublicDraft($event);
        $source = app(PublicEventSourceQuery::class)->get($event);
        auth()->logout();
        session()->flush();
        if ($actor !== 'guest') {
            $promotion = $actor === 'outsider' ? Promotion::factory()->create() : $event->promotion()->firstOrFail();
            actingAsPromotionMember($promotion, $actor === 'outsider' ? MembershipRole::Owner : MembershipRole::Member);
        }

        // Act
        $response = $operation === 'update'
            ? put(route('events.public-pages.update', $event), [
                'complete' => 1,
                'version' => $publication->version,
                'fingerprint' => $source['fingerprint'],
                'announcement' => 'scheduled',
                'summary' => 'Do not save',
            ])
            : get(route('events.public-pages.'.$operation, $event));

        // Assert
        if ($actor === 'guest') {
            $response->assertRedirect(route('login'));
        } elseif ($actor === 'outsider') {
            $response->assertNotFound();
        } else {
            $response->assertForbidden();
        }

        expect(freshModel($publication)->getAttributes())->toBe($publication->getAttributes());
    })->with([
        'edit' => 'edit',
        'show' => 'show',
        'update' => 'update',
    ])->with([
        'guest' => 'guest',
        'member' => 'member',
        'outsider' => 'outsider',
    ]);
});
