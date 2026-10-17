<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ViewModels\Events\EventPackViewModel;
use Illuminate\View\View;
use PHPUnit\Framework\Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

describe('show', function (): void {
    test('an event pack opens from the saved event without creating data', function () {
        // Arrange
        $event = Event::factory()
            ->create(['name' => 'Harbor Showcase']);

        actingAs(administrator());

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack')
            ->assertViewHas('event', $event)
            ->assertSee('Harbor Showcase')
            ->assertSee('Event pack');

        expect($event->pack()
            ->exists())->toBeFalse();
    });

    test('the roster expands teams and deduplicates people while retaining deleted bookings', function () {
        // Arrange
        $event = Event::factory()
            ->create();
        $team = TagTeam::factory()
            ->bookable()
            ->create(['name' => 'Harbor Crew']);
        $wrestler = $team->currentWrestlers()
            ->firstOrFail();
        $referee = Referee::factory()
            ->bookable()
            ->create();
        $first = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$team])
            ->create(['match_number' => 1]);
        $second = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$wrestler])
            ->create(['match_number' => 2]);
        $first->referees()
            ->attach($referee);
        $second->referees()
            ->attach($referee);
        $wrestler->delete();
        $referee->delete();

        actingAs(administrator());

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack')
            ->assertViewHas('event', $event);

        $rosterData = data_get($response->viewData('card'), 'roster');
        Assert::assertIsArray($rosterData);
        $roster = collect($rosterData);
        expect($roster)->toHaveCount(3)
            ->and($roster->firstWhere('key', 'wrestler:'.$wrestler->id))
            ->toMatchArray([
                'matches' => [1, 2],
                'warning' => true,
            ])
            ->and($roster->firstWhere('key', 'referee:'.$referee->id))
            ->toMatchArray([
                'matches' => [1, 2],
                'warning' => true,
            ]);
    });
    test('saved pack timings and notes reopen across midnight', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'timezone' => 'America/New_York', 'doors_at' => '2026-11-14T23:30', 'revision' => '02',
            'master' => 'Morgan Ellis', 'notes' => 'Confirm the replacement before doors.', 'follow_up' => 'Record results after the show.',
            'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Intermission', 'after_match_id' => null,
                'planned_at' => '2026-11-15T00:10', 'revised_at' => null, 'minutes' => 15, 'notes' => 'Restart on time.']]];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack')
            ->assertViewHas('event', $event)
            ->assertSee('Confirm the replacement before doors.')
            ->assertSee('2026-11-15T00:10')
            ->assertSee('Morgan Ellis');

        expect(EventPack::query()
            ->count())->toBe(1);
    });

    test('removed match notes survive while the ordered card excludes the deleted match', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        [$later, $first] = EventMatch::factory()
            ->for($event)
            ->count(2)
            ->sequence(['match_number' => 7], ['match_number' => 2])
            ->create()
            ->all();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'matches' => [['match_id' => $later->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => 'Keep this with match seven.']]];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $later->delete();

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack')
            ->assertViewHas('event', $event);

        expect(data_get($response->viewData('card'), 'rows.*.id'))->toBe([$first->id])
            ->and($response->viewData('needsReview'))
            ->toBeTrue()
            ->and(data_get($response->viewData('content'), 'matches.0.notes'))
            ->toBe('Keep this with match seven.');
    });

    test('restored matches return in explicit card order', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        [$later, $first] = EventMatch::factory()
            ->for($event)
            ->count(2)
            ->sequence(['match_number' => 7], ['match_number' => 2])
            ->create()
            ->all();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'matches' => [['match_id' => $later->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => 'Keep this with match seven.']]];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $later->delete();
        $later->restore();

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack')
            ->assertViewHas('event', $event);

        expect(data_get($response->viewData('card'), 'rows.*.id'))->toBe([$first->id, $later->id]);
    });

    test('members and managers can read their event pack', function (MembershipRole $role, bool $print, string $view): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion)
            ->create();

        actingAsPromotionMember($promotion, $role);

        // Act
        $response = get(route('events.pack.show', ['event' => $event, 'print' => $print]));

        // Assert
        $response
            ->assertOk()
            ->assertViewHas('event', $event);

        Assert::assertInstanceOf(View::class, $response->original);
        expect($response->original->name())->toBe($view)
            ->and($event->pack()
                ->exists())
            ->toBeFalse();
    })->with(['member' => MembershipRole::Member, 'manager' => MembershipRole::Manager])
        ->with(['screen' => [false, 'events.pack'], 'print' => [true, 'events.pack-print']]);

    test('members and managers cannot read a foreign event pack', function (MembershipRole $role): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion)
            ->create();

        actingAsPromotionMember($promotion, $role);
        $foreign = Event::factory()
            ->for(Promotion::factory())
            ->create();

        // Act
        $response = get(route('events.pack.show', $foreign));

        // Assert
        $response
            ->assertNotFound();
    })->with(['member' => MembershipRole::Member, 'manager' => MembershipRole::Manager]);

    test('guests cannot read or print a pack', function (bool $print): void {
        // Arrange
        $event = Event::factory()
            ->create();

        // Act
        $response = get(route('events.pack.show', ['event' => $event, 'print' => $print]));

        // Assert
        $response
            ->assertRedirect(route('login'));

        expect($event->pack()
            ->exists())->toBeFalse();
    })->with(['screen' => false, 'print' => true]);

    test('empty print pages label an unset schedule and venue without saving data', function (): void {
        // Arrange
        $event = Event::factory()
            ->create(['date' => null, 'venue_id' => null]);

        actingAs(administrator());

        // Act
        $response = get(route('events.pack.show', ['event' => $event, 'print' => 1]));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack-print')
            ->assertViewHas('event', $event)
            ->assertSee('Unscheduled')
            ->assertSee('Venue not set');

        expect($event->pack()
            ->exists())->toBeFalse();
    });

    test('printing escapes saved notes and ignores unsaved query input', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'notes' => '<script>alert(1)</script>'];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);

        // Act
        $response = get(route('events.pack.show', ['event' => $event, 'print' => 1, 'notes' => 'Unsaved injection']));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack-print')
            ->assertViewHas('event', $event)
            ->assertSee('<script>alert(1)</script>')
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertDontSee('Unsaved injection');
    });

    test('a removed anchor is displayed as an unplaced segment', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        $match = EventMatch::factory()
            ->for($event)
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Intermission', 'after_match_id' => $match->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null]]];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $match->delete();

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack')
            ->assertViewHas('event', $event);

        expect($response->viewData('unplaced'))->toHaveCount(1);
    });

    test('a deleted event has no pack page', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        $match = EventMatch::factory()
            ->for($event)
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Intermission', 'after_match_id' => $match->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null]]];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $match->delete();
        $event->delete();

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertNotFound();
    });

    test('restoring a deleted event preserves its saved pack', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        $match = EventMatch::factory()
            ->for($event)
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Intermission', 'after_match_id' => $match->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null]]];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $match->delete();
        $event->delete();
        $event->restore();

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack')
            ->assertViewHas('event', $event);

        expect($event->pack()
            ->firstOrFail()->content['segments'][0]['label'])->toBe('Intermission');
    });

    test('reordering and adding matches keep timings and anchors on stable identities', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        [$first, $second] = EventMatch::factory()
            ->for($event)
            ->count(2)
            ->sequence(['match_number' => 1], ['match_number' => 2])
            ->create()
            ->all();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'timezone' => 'America/New_York', 'matches' => [['match_id' => $first->id, 'planned_at' => '2026-11-14T23:50', 'revised_at' => null, 'minutes' => null, 'notes' => 'First identity']], 'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Break', 'after_match_id' => $first->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null]]];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $first->update(['match_number' => 3]);
        $second->update(['match_number' => 1]);
        EventMatch::factory()
            ->for($event)
            ->create(['match_number' => 2]);

        // Act
        $response = get(route('events.pack.show', ['event' => $event, 'print' => 1]));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack-print')
            ->assertViewHas('event', $event);

        $rows = $response->viewData('running');
        expect($response->viewData('needsReview'))->toBeTrue()
            ->and($rows)
            ->toHaveCount(4)
            ->and(data_get($rows, '0.timing'))
            ->toBe([])
            ->and(data_get($rows, '1.timing'))
            ->toBe([])
            ->and(data_get($rows, '2.timing.notes'))
            ->toBe('First identity')
            ->and(data_get($rows, '3.label'))
            ->toBe('Break')
            ->and(data_get($response->viewData('content'), 'timezone'))
            ->toBe('America/New_York');
    });

    test('a date change flags review without replacing saved timings', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create(['timezone' => 'America/New_York']);
        $event = Event::factory()
            ->for($promotion)
            ->scheduled()
            ->create();
        $wrestler = Wrestler::factory()
            ->for($promotion)
            ->create(['name' => 'Harbor Ace']);
        $match = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$wrestler])
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'timezone' => 'America/New_York', 'matches' => [['match_id' => $match->id, 'planned_at' => '2026-11-14T23:50', 'revised_at' => null, 'minutes' => null, 'notes' => 'Keep the cue']]];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $event->update(['date' => now()->addWeek()]);

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack')
            ->assertViewHas('event', $event);

        expect($response->viewData('needsReview'))->toBeTrue()
            ->and(data_get($response->viewData('content'), 'timezone'))
            ->toBe('America/New_York')
            ->and(data_get($response->viewData('content'), 'matches.0.planned_at'))
            ->toBe('2026-11-14T23:50')
            ->and(data_get($response->viewData('content'), 'matches.0.notes'))
            ->toBe('Keep the cue');
    });

    test('a timezone change flags review without replacing saved timings', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create(['timezone' => 'America/New_York']);
        $event = Event::factory()
            ->for($promotion)
            ->scheduled()
            ->create();
        $wrestler = Wrestler::factory()
            ->for($promotion)
            ->create(['name' => 'Harbor Ace']);
        $match = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$wrestler])
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'timezone' => 'America/New_York', 'matches' => [['match_id' => $match->id, 'planned_at' => '2026-11-14T23:50', 'revised_at' => null, 'minutes' => null, 'notes' => 'Keep the cue']]];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $promotion->update(['timezone' => 'America/Chicago']);

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack')
            ->assertViewHas('event', $event);

        expect($response->viewData('needsReview'))->toBeTrue()
            ->and(data_get($response->viewData('content'), 'timezone'))
            ->toBe('America/New_York')
            ->and(data_get($response->viewData('content'), 'matches.0.planned_at'))
            ->toBe('2026-11-14T23:50')
            ->and(data_get($response->viewData('content'), 'matches.0.notes'))
            ->toBe('Keep the cue');
    });

    test('a competitor change flags review without replacing saved timings', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create(['timezone' => 'America/New_York']);
        $event = Event::factory()
            ->for($promotion)
            ->scheduled()
            ->create();
        $wrestler = Wrestler::factory()
            ->for($promotion)
            ->create(['name' => 'Harbor Ace']);
        $match = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$wrestler])
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'timezone' => 'America/New_York', 'matches' => [['match_id' => $match->id, 'planned_at' => '2026-11-14T23:50', 'revised_at' => null, 'minutes' => null, 'notes' => 'Keep the cue']]];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $wrestler->update(['name' => 'Harbor Ace II']);

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.pack')
            ->assertViewHas('event', $event);

        expect($response->viewData('needsReview'))->toBeTrue()
            ->and(data_get($response->viewData('content'), 'timezone'))
            ->toBe('America/New_York')
            ->and(data_get($response->viewData('content'), 'matches.0.planned_at'))
            ->toBe('2026-11-14T23:50')
            ->and(data_get($response->viewData('content'), 'matches.0.notes'))
            ->toBe('Keep the cue');
    });

});

describe('update', function (): void {
    test('saving persists complete timings and notes across midnight', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();

        $match = EventMatch::factory()
            ->for($event)
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $data['card']['fingerprint'], 'timezone' => 'America/New_York', 'doors_at' => '2026-11-14T23:30', 'revision' => '02',
            'master' => 'Morgan Ellis', 'notes' => 'Confirm the replacement before doors.', 'follow_up' => 'Record results after the show.',
            'matches' => [['match_id' => (string) $match->id, 'planned_at' => '2026-11-14T23:50', 'revised_at' => null, 'minutes' => '12', 'notes' => 'Keep the opening cue']],
            'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Intermission', 'after_match_id' => null,
                'planned_at' => '2026-11-15T00:10', 'revised_at' => null, 'minutes' => 15, 'notes' => 'Restart on time.']]];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('events.pack.show', $event))
            ->assertSessionHas('success', __('event-pack.saved'));

        expect($event->pack()
            ->firstOrFail()->content)->toMatchArray([
                'timezone' => 'America/New_York', 'doors_at' => '2026-11-14T23:30', 'revision' => '02',
                'master' => 'Morgan Ellis', 'notes' => 'Confirm the replacement before doors.', 'follow_up' => 'Record results after the show.',
                'matches' => [['match_id' => $match->id, 'planned_at' => '2026-11-14T23:50', 'revised_at' => null, 'minutes' => 12, 'notes' => 'Keep the opening cue']],
                'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Intermission', 'after_match_id' => null,
                    'planned_at' => '2026-11-15T00:10', 'revised_at' => null, 'minutes' => 15, 'notes' => 'Restart on time.']],
            ])
            ->and($event->pack()
                ->firstOrFail()->version)
            ->toBe(1)
            ->and(EventPack::query()
                ->count())
            ->toBe(1);
    });

    test('a stale tab cannot overwrite a saved pack and keeps its submitted notes', function () {
        // Arrange
        $event = Event::factory()
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $payload = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $data['card']['fingerprint'],
            'timezone' => 'UTC',
            'notes' => 'First save',
        ];

        // The earlier save is the baseline the stale tab must not overwrite.
        $baselineResponse = put(route('events.pack.update', $event), $payload);

        $baselineResponse
            ->assertSessionHasNoErrors();

        $stalePayload = [...$payload, 'notes' => 'Stale notes'];

        // Act
        $response = from(route('events.pack.show', $event))->put(route('events.pack.update', $event), $stalePayload);

        // Assert
        $response
            ->assertSessionHasErrors(['pack' => 'This was changed by someone else. Reload to see the latest version, review it, then try again.'])
            ->assertSessionHasInput('notes', 'Stale notes');

        expect($event->pack()
            ->firstOrFail()->content['notes'])->toBe('First save')
            ->and($event->pack()
                ->firstOrFail()->version)
            ->toBe(1);
    });

    test('a changed source rejects a stale save', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'notes' => 'Saved first'];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $event->update(['name' => 'Changed show']);
        $requestData = ['complete' => 1, 'version' => 1, 'fingerprint' => $data['card']['fingerprint'], 'timezone' => 'UTC', 'notes' => 'Replacement'];

        // Act
        $response = from(route('events.pack.show', $event))->put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors('pack')
            ->assertSessionHasInput('notes', 'Replacement');

        expect($event->pack()
            ->firstOrFail()->version)->toBe(1)
            ->and($event->pack()
                ->firstOrFail()->content['notes'])
            ->toBe('Saved first');
    });

    test('a changed source requires review even with its current fingerprint', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'notes' => 'Saved first'];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $event->update(['name' => 'Changed show']);
        $currentData = resolve(EventPackViewModel::class)->data($event);
        $requestData = ['complete' => 1, 'version' => 1, 'fingerprint' => $currentData['card']['fingerprint'], 'timezone' => 'UTC', 'notes' => 'Replacement'];

        // Act
        $response = from(route('events.pack.show', $event))->put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors('pack')
            ->assertSessionHasInput('notes', 'Replacement');

        expect($event->pack()
            ->firstOrFail()->version)->toBe(1)
            ->and($event->pack()
                ->firstOrFail()->content['notes'])
            ->toBe('Saved first');
    });

    test('a reviewed current source can replace the saved pack', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'notes' => 'Saved first'];
        EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content]);
        $event->update(['name' => 'Changed show']);
        $currentData = resolve(EventPackViewModel::class)->data($event);
        $requestData = ['complete' => 1, 'version' => 1, 'fingerprint' => $currentData['card']['fingerprint'], 'timezone' => 'UTC', 'notes' => 'Replacement', 'reviewed' => 1];

        // Act
        $response = from(route('events.pack.show', $event))->put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('events.pack.show', $event));

        expect($event->pack()
            ->firstOrFail()->version)->toBe(2)
            ->and($event->pack()
                ->firstOrFail()->content['notes'])
            ->toBe('Replacement');
    });

    test('nested matches from another event are refused atomically', function () {
        // Arrange
        $event = Event::factory()
            ->create();
        $other = EventMatch::factory()
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $data['card']['fingerprint'],
            'timezone' => 'UTC',
            'notes' => 'Do not save',
            'matches' => [[
                'match_id' => $other->id,
                'minutes' => 12,
            ]],
            'segments' => [[
                'id' => '1f64fc86-5bbf-4a41-952d-b85800358f00',
                'label' => 'Break',
                'after_match_id' => $other->id,
            ]],
        ];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors(['matches.0.match_id', 'segments.0.after_match_id'])
            ->assertSessionHasInput('notes', 'Do not save');

        expect($event->pack()
            ->exists())->toBeFalse();
    });

    test('an incomplete or invalid form cannot save partial data', function () {
        // Arrange
        $event = Event::factory()
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);

        $requestData = [
            'version' => 0,
            'fingerprint' => $data['card']['fingerprint'],
            'timezone' => 'Invalid/Zone',
            'doors_at' => '2026-02-31T25:61',
            'notes' => 'Preserve me',
        ];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors(['complete', 'timezone', 'doors_at'])
            ->assertSessionHasInput('notes', 'Preserve me');

        expect($event->pack()
            ->exists())->toBeFalse();
    });

    test('members and managers cannot update a foreign event pack', function (MembershipRole $role): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion)
            ->create();

        actingAsPromotionMember($promotion, $role);
        $foreign = Event::factory()
            ->for(Promotion::factory())
            ->create();

        $requestData = [];

        // Act
        $response = put(route('events.pack.update', $foreign), $requestData);

        // Assert
        $response
            ->assertNotFound();

        expect($foreign->pack()
            ->withoutGlobalScopes()
            ->exists())->toBeFalse();
    })->with(['member' => MembershipRole::Member, 'manager' => MembershipRole::Manager]);

    test('owners and managers can save their event pack', function (MembershipRole $role): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion)
            ->create();

        actingAsPromotionMember($promotion, $role);
        $data = resolve(EventPackViewModel::class)->data($event);
        $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $data['card']['fingerprint'], 'timezone' => 'UTC'];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('events.pack.show', $event));

        expect($event->pack()
            ->exists())->toBeTrue();
    })->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

    test('read-only members cannot save their event pack', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion)
            ->create();

        actingAsPromotionMember($promotion, MembershipRole::Member);
        $data = resolve(EventPackViewModel::class)->data($event);
        $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $data['card']['fingerprint'], 'timezone' => 'UTC'];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertForbidden();

        expect($event->pack()
            ->exists())->toBeFalse();
    });

    test('guests cannot save a pack', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();

        $requestData = [];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect(route('login'));

        expect($event->pack()
            ->exists())->toBeFalse();
    });

    test('print after saving redirects to the persisted print view', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $data['card']['fingerprint'], 'timezone' => 'UTC', 'notes' => '<script>alert(1)</script>', 'print_after' => 1];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect(route('events.pack.show', ['event' => $event, 'print' => 1]))
            ->assertSessionHasNoErrors();

        expect($event->pack()
            ->firstOrFail()->content['notes'])->toBe('<script>alert(1)</script>');
    });

    test('a removed anchor cannot be saved even after review', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        $match = EventMatch::factory()
            ->for($event)
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $content = [...$data['content'], 'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Intermission', 'after_match_id' => $match->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null]]];
        $pack = EventPack::factory()
            ->for($event)
            ->create(['version' => 1, 'card_fingerprint' => $data['card']['fingerprint'], 'content' => $content])
            ->refresh();
        $originalContent = $pack->content;
        $match->delete();
        $currentData = resolve(EventPackViewModel::class)->data($event);
        $requestData = ['complete' => 1, 'version' => 1, 'fingerprint' => $currentData['card']['fingerprint'], 'timezone' => 'UTC', 'reviewed' => 1, 'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Intermission', 'after_match_id' => $match->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null]]];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertSessionHasErrors('segments.0.after_match_id');

        expect($event->pack()
            ->firstOrFail()->content)->toBe($originalContent)
            ->and($event->pack()
                ->firstOrFail()->version)
            ->toBe(1);
    });

});

describe('validated pack content', function (): void {
    test('omitted and null collections persist the same empty content defaults', function (array $collections): void {
        // Arrange
        $event = Event::factory()
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $data['card']['fingerprint'],
            'timezone' => 'UTC',
            ...$collections,
        ];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect(route('events.pack.show', $event))
            ->assertSessionHasNoErrors();

        $pack = $event->pack()
            ->firstOrFail();
        expect($pack->content)->toEqual([
            'timezone' => 'UTC',
            'doors_at' => null,
            'revision' => null,
            'master' => null,
            'notes' => null,
            'follow_up' => null,
            'matches' => [],
            'segments' => [],
        ])->and($pack->version)
            ->toBe(1);
    })->with([
        'omitted lists' => [[]],
        'null lists' => [
            [
                'matches' => null,
                'segments' => null,
            ],
        ],
    ]);

    test('numeric strings become integer identities and minutes without storing editor controls', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        $match = EventMatch::factory()
            ->for($event)
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);
        $segmentId = 'e8027dd9-03ce-4237-b3be-b306679da858';

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $data['card']['fingerprint'],
            'timezone' => 'UTC',
            'reviewed' => 1,
            'print_after' => '0',
            'unexpected' => 'Do not store',
            'matches' => [
                [
                    'match_id' => (string) $match->id,
                    'minutes' => '12',
                ],
            ],
            'segments' => [
                [
                    'id' => $segmentId,
                    'label' => 'Break',
                    'after_match_id' => (string) $match->id,
                    'minutes' => '5',
                ],
            ],
        ];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect(route('events.pack.show', $event))
            ->assertSessionHasNoErrors();

        $content = $event->pack()
            ->firstOrFail()->content;
        expect($content)->toEqual([
            'timezone' => 'UTC',
            'matches' => [
                [
                    'match_id' => $match->id,
                    'minutes' => 12,
                    'planned_at' => null,
                    'revised_at' => null,
                    'notes' => null,
                ],
            ],
            'segments' => [
                [
                    'id' => $segmentId,
                    'label' => 'Break',
                    'after_match_id' => $match->id,
                    'minutes' => 5,
                    'planned_at' => null,
                    'revised_at' => null,
                    'notes' => null,
                ],
            ],
            'doors_at' => null,
            'revision' => null,
            'master' => null,
            'notes' => null,
            'follow_up' => null,
        ])->and($content['matches'][0]['match_id'])->toBe($match->id)
            ->and($content['matches'][0]['minutes'])->toBe(12)
            ->and($content['segments'][0]['after_match_id'])->toBe($match->id)
            ->and($content['segments'][0]['minutes'])->toBe(5);
    });

    test('unknown nested match keys cannot be persisted', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        $match = EventMatch::factory()
            ->for($event)
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $data['card']['fingerprint'],
            'timezone' => 'UTC',
            'notes' => 'Retain my input',
            'matches' => [
                [
                    'match_id' => $match->id,
                    'unexpected' => 'Do not store',
                ],
            ],
        ];

        // Act
        $response = from(route('events.pack.show', $event))
            ->put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect(route('events.pack.show', $event))
            ->assertSessionHasErrors('matches.0')
            ->assertSessionHasInput('notes', 'Retain my input');

        expect($event->pack()
            ->exists())->toBeFalse();
    });

    test('duplicate match identities cannot create a pack', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        $match = EventMatch::factory()
            ->for($event)
            ->create();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $data['card']['fingerprint'],
            'timezone' => 'UTC',
            'matches' => [['match_id' => $match->id], ['match_id' => $match->id]],
        ];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect()
            ->assertSessionHasErrors(['matches.0.match_id', 'matches.1.match_id'])
            ->assertSessionHasInput('matches', $requestData['matches']);

        expect($event->pack()
            ->exists())->toBeFalse();
    });

    test('notes for a removed match can still be saved as historical content', function (): void {
        // Arrange
        $event = Event::factory()
            ->create();
        $match = EventMatch::factory()
            ->for($event)
            ->create();
        $match->delete();

        actingAs(administrator());
        $data = resolve(EventPackViewModel::class)->data($event);

        $requestData = [
            'complete' => 1,
            'version' => 0,
            'fingerprint' => $data['card']['fingerprint'],
            'timezone' => 'UTC',
            'matches' => [
                [
                    'match_id' => $match->id,
                    'notes' => 'Keep this historical note',
                ],
            ],
        ];

        // Act
        $response = put(route('events.pack.update', $event), $requestData);

        // Assert
        $response
            ->assertRedirect(route('events.pack.show', $event))
            ->assertSessionHasNoErrors();

        $content = $event->pack()
            ->firstOrFail()->content;
        expect($content['matches'])->toEqual([
            [
                'match_id' => $match->id,
                'notes' => 'Keep this historical note',
                'planned_at' => null,
                'revised_at' => null,
                'minutes' => null,
            ],
        ]);
    });
});

describe('production privacy', function (): void {
    test('a member pack response omits private production content before rendering', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()
            ->for($promotion)
            ->create();

        $pack = EventPack::factory()
            ->for($event)
            ->create();
        $pack->content = [...$pack->content, 'production' => ['notes' => 'CREW-PRIVATE-7Q', 'assignments' => [], 'cues' => []]];
        $pack->save();

        $member = basicUser();
        $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
        actingAs($member);

        // Act
        $response = get(route('events.pack.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertDontSee('CREW-PRIVATE-7Q');

        expect($response->viewData('content'))->not->toHaveKey('production')
            ->and(data_get($response->viewData('pack'), 'content'))->not->toHaveKey('production');
    });
});
