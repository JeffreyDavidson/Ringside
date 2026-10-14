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

use function Pest\Laravel\actingAs;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

test('an event pack opens from the saved event without creating data', function () {
    // Arrange
    $event = Event::factory()->create(['name' => 'Harbor Showcase']);
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

    expect($event->pack()->exists())->toBeFalse();
});

test('saved pack timings and notes reopen for their event including midnight dates', function () {
    // Arrange
    $event = Event::factory()->create();
    actingAs(administrator());
    $page = get(route('events.pack.show', $event));

    // Act
    $response = put(route('events.pack.update', $event), [
        'complete' => 1, 'version' => 0,
        'fingerprint' => data_get($page->viewData('card'), 'fingerprint'),
        'timezone' => 'America/New_York',
        'doors_at' => '2026-11-14T23:30',
        'revision' => '02',
        'master' => 'Morgan Ellis',
        'notes' => 'Confirm the replacement before doors.',
        'follow_up' => 'Record results after the show.',
        'segments' => [[
            'id' => '1f64fc86-5bbf-4a41-952d-b85800358f00',
            'label' => 'Intermission', 'after_match_id' => null,
            'planned_at' => '2026-11-15T00:10', 'revised_at' => null,
            'minutes' => 15, 'notes' => 'Restart on time.',
        ]],
    ]);

    // Assert

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('events.pack.show', $event))
        ->assertSessionHas('success', __('event-pack.saved'));

    expect($event->pack()->firstOrFail()->content)->toMatchArray(['timezone' => 'America/New_York', 'notes' => 'Confirm the replacement before doors.', 'master' => 'Morgan Ellis']);

    // Act
    $response = get(route('events.pack.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Confirm the replacement before doors.')
        ->assertSee('2026-11-15T00:10')
        ->assertSee('Morgan Ellis');

    expect(EventPack::query()->count())->toBe(1);
});

test('the pack uses the complete ordered card and preserves removed match notes', function () {
    // Arrange
    $event = Event::factory()->create();
    $later = EventMatch::factory()->for($event)->create(['match_number' => 7]);
    $first = EventMatch::factory()->for($event)->create(['match_number' => 2]);
    actingAs(administrator());
    $page = get(route('events.pack.show', $event));
    $payload = ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'UTC',
        'matches' => [['match_id' => $later->id, 'notes' => 'Keep this with match seven.']]];

    // Act
    $response = put(route('events.pack.update', $event), $payload);

    // Assert
    $response->assertSessionHasNoErrors();

    $later->delete();
    $page = get(route('events.pack.show', $event));

    expect(data_get($page->viewData('card'), 'rows.*.id'))->toBe([$first->id])
        ->and($page->viewData('needsReview'))->toBeTrue()
        ->and(data_get($page->viewData('content'), 'matches.0.notes'))->toBe('Keep this with match seven.');
    $later->restore();
    $page = get(route('events.pack.show', $event));
    expect(data_get($page->viewData('card'), 'rows.*.id'))->toBe([$first->id, $later->id]);
});

test('the roster expands teams and deduplicates people while retaining deleted bookings', function () {
    // Arrange
    $event = Event::factory()->create();
    $team = TagTeam::factory()->bookable()->create(['name' => 'Harbor Crew']);
    $wrestler = $team->currentWrestlers()->firstOrFail();
    $referee = Referee::factory()->bookable()->create();
    $first = EventMatch::factory()->for($event)->withCompetitors([$team])->create(['match_number' => 1]);
    $second = EventMatch::factory()->for($event)->withCompetitors([$wrestler])->create(['match_number' => 2]);
    $first->referees()->attach($referee);
    $second->referees()->attach($referee);
    $wrestler->delete();
    $referee->delete();
    actingAs(administrator());

    // Act
    $response = get(route('events.pack.show', $event));

    // Assert

    $response->assertOk();

    $rosterData = data_get($response->viewData('card'), 'roster');
    assert(is_array($rosterData));
    $roster = collect($rosterData);
    expect($roster)->toHaveCount(3)
        ->and($roster->firstWhere('key', 'wrestler:'.$wrestler->id))->toMatchArray(['matches' => [1, 2], 'warning' => true])
        ->and($roster->firstWhere('key', 'referee:'.$referee->id))->toMatchArray(['matches' => [1, 2], 'warning' => true]);
});

test('a stale tab cannot overwrite a saved pack and keeps its submitted notes', function () {
    // Arrange
    $event = Event::factory()->create();
    actingAs(administrator());
    $page = get(route('events.pack.show', $event));
    $payload = ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'UTC', 'notes' => 'First save'];
    // Act
    $response = put(route('events.pack.update', $event), $payload);

    // Assert
    $response->assertSessionHasNoErrors();

    // Act
    $response = from(route('events.pack.show', $event))->put(route('events.pack.update', $event), [...$payload, 'notes' => 'Stale notes']);

    $response
        ->assertSessionHasErrors('pack')
        ->assertSessionHasInput('notes', 'Stale notes');

    expect($event->pack()->firstOrFail()->content['notes'])->toBe('First save')
        ->and($event->pack()->firstOrFail()->version)->toBe(1);
});

test('card changes reject stale saves and require review on reopen', function () {
    // Arrange
    $event = Event::factory()->create();
    actingAs(administrator());
    $page = get(route('events.pack.show', $event));
    $payload = ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'UTC'];
    // Act
    $response = put(route('events.pack.update', $event), $payload);

    // Assert
    $response->assertSessionHasNoErrors();

    $event->update(['name' => 'Changed show']);

    // Act
    $response = put(route('events.pack.update', $event), [...$payload, 'version' => 1]);

    // Assert
    $response->assertSessionHasErrors('pack');

    $page = get(route('events.pack.show', $event));
    $payload = [...$payload, 'version' => 1, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint')];

    // Act
    $response = put(route('events.pack.update', $event), $payload);

    // Assert
    $response->assertSessionHasErrors('pack');

    // Act
    $response = put(route('events.pack.update', $event), [...$payload, 'reviewed' => 1]);

    // Assert
    $response->assertSessionHasNoErrors();

    expect($event->pack()->firstOrFail()->version)->toBe(2);
});

test('nested matches from another event are refused atomically', function () {
    // Arrange
    $event = Event::factory()->create();
    $other = EventMatch::factory()->create();
    actingAs(administrator());
    $page = get(route('events.pack.show', $event));

    // Act
    $response = put(route('events.pack.update', $event), ['complete' => 1, 'version' => 0,
        'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'UTC', 'notes' => 'Do not save',
        'matches' => [['match_id' => $other->id, 'minutes' => 12]],
        'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Break', 'after_match_id' => $other->id]],
    ]);

    // Assert

    $response
        ->assertSessionHasErrors(['matches.0.match_id', 'segments.0.after_match_id'])
        ->assertSessionHasInput('notes', 'Do not save');

    expect($event->pack()->exists())->toBeFalse();
});

test('members can read and print but only managers can save within their promotion', function (MembershipRole $role, bool $canSave) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create();
    $other = Event::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $page = get(route('events.pack.show', $event));
    $page->assertOk();
    // Act
    $response = get(route('events.pack.show', ['event' => $event, 'print' => 1]));

    // Assert
    $response->assertOk();

    // Act
    $response = get(route('events.pack.show', $other));

    // Assert
    $response->assertNotFound();

    // Act
    $response = put(route('events.pack.update', $other), []);

    // Assert
    $response->assertNotFound();

    // Act
    $response = put(route('events.pack.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'UTC']);

    if ($canSave) {
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        expect($event->pack()->exists())->toBeTrue();

        return;
    }
    $response->assertForbidden();

    expect($event->pack()->exists())->toBeFalse();
})->with([
    'member' => [MembershipRole::Member, false],
    'manager' => [MembershipRole::Manager, true],
]);

test('guests cannot view print or save a pack', function () {
    // Arrange
    $event = Event::factory()->create();

    // Act
    $response = get(route('events.pack.show', $event));

    // Assert
    $response->assertRedirect(route('login'));

    // Act
    $response = get(route('events.pack.show', ['event' => $event, 'print' => 1]));

    // Assert
    $response->assertRedirect(route('login'));

    // Act
    $response = put(route('events.pack.update', $event), []);

    // Assert
    $response->assertRedirect(route('login'));

    expect($event->pack()->exists())->toBeFalse();
});

test('printing uses saved values and escapes notes without saving a record', function () {
    // Arrange
    $event = Event::factory()->create(['date' => null, 'venue_id' => null]);
    actingAs(administrator());

    // Act
    $response = get(route('events.pack.show', ['event' => $event, 'print' => 1]));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Unscheduled')
        ->assertSee('Venue not set');

    expect($event->pack()->exists())->toBeFalse();
    $page = get(route('events.pack.show', $event));

    // Act
    $response = put(route('events.pack.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'UTC', 'notes' => '<script>alert(1)</script>', 'print_after' => 1]);

    // Assert
    $response
        ->assertRedirect(route('events.pack.show', ['event' => $event, 'print' => 1]));

    // Act
    $response = get(route('events.pack.show', ['event' => $event, 'print' => 1, 'notes' => 'Unsaved injection']));

    $response
        ->assertOk()
        ->assertViewIs('events.pack-print')
        ->assertViewHas('event', $event)
        ->assertSee('<script>alert(1)</script>')
        ->assertDontSeeHtml('<script>alert(1)</script>')
        ->assertDontSee('Unsaved injection');
});

test('removed anchors require placement and soft deleting the event preserves its pack', function () {
    // Arrange
    $event = Event::factory()->create();
    $match = EventMatch::factory()->for($event)->create();
    actingAs(administrator());
    $page = get(route('events.pack.show', $event));
    $segment = ['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Intermission', 'after_match_id' => $match->id];
    // Act
    $response = put(route('events.pack.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'UTC', 'segments' => [$segment]]);

    // Assert
    $response->assertSessionHasNoErrors();

    $match->delete();
    $page = get(route('events.pack.show', $event));
    expect($page->viewData('unplaced'))->toHaveCount(1);

    // Act
    $response = put(route('events.pack.update', $event), ['complete' => 1, 'version' => 1, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'UTC', 'reviewed' => 1, 'segments' => [$segment]]);

    // Assert
    $response->assertSessionHasErrors('segments.0.after_match_id');

    $event->delete();

    // Act
    $response = get(route('events.pack.show', $event));

    // Assert
    $response->assertNotFound();

    $event->restore();

    // Act
    $response = get(route('events.pack.show', $event));

    // Assert
    $response->assertOk();

    expect($event->pack()->firstOrFail()->content['segments'][0]['label'])->toBe('Intermission');
});

test('reordering and adding matches keep timings and segment anchors on stable identities', function () {
    // Arrange
    $event = Event::factory()->create();
    $first = EventMatch::factory()->for($event)->create(['match_number' => 1]);
    $second = EventMatch::factory()->for($event)->create(['match_number' => 2]);
    actingAs(administrator());
    $page = get(route('events.pack.show', $event));
    // Act
    $response = put(route('events.pack.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'America/New_York',
        'matches' => [['match_id' => $first->id, 'planned_at' => '2026-11-14T23:50', 'notes' => 'First identity']],
        'segments' => [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Break', 'after_match_id' => $first->id]],
    ]);

    // Assert
    $response->assertSessionHasNoErrors();

    $first->update(['match_number' => 3]);
    $second->update(['match_number' => 1]);
    EventMatch::factory()->for($event)->create(['match_number' => 2]);

    $page = get(route('events.pack.show', ['event' => $event, 'print' => 1]));

    $rows = $page->viewData('running');
    expect($page->viewData('needsReview'))->toBeTrue()
        ->and($rows)->toHaveCount(4)
        ->and(data_get($rows, '0.timing'))->toBe([])
        ->and(data_get($rows, '1.timing'))->toBe([])
        ->and(data_get($rows, '2.timing.notes'))->toBe('First identity')
        ->and(data_get($rows, '3.label'))->toBe('Break')
        ->and(data_get($page->viewData('content'), 'timezone'))->toBe('America/New_York');
});

test('an incomplete or invalid form cannot save partial data', function () {
    // Arrange
    $event = Event::factory()->create();
    actingAs(administrator());
    $page = get(route('events.pack.show', $event));

    // Act
    $response = put(route('events.pack.update', $event), ['version' => 0, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'Invalid/Zone', 'doors_at' => '2026-02-31T25:61', 'notes' => 'Preserve me']);

    // Assert

    $response
        ->assertSessionHasErrors(['complete', 'timezone', 'doors_at'])
        ->assertSessionHasInput('notes', 'Preserve me');

    expect($event->pack()->exists())->toBeFalse();
});

test('date timezone and competitor changes flag review without changing saved timings', function (string $change): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $event = Event::factory()->for($promotion)->scheduled()->create();
    $wrestler = Wrestler::factory()->for($promotion)->create(['name' => 'Harbor Ace']);
    $match = EventMatch::factory()->for($event)->withCompetitors([$wrestler])->create();
    actingAs(administrator());
    $page = get(route('events.pack.show', $event));
    $response = put(route('events.pack.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($page->viewData('card'), 'fingerprint'), 'timezone' => 'America/New_York',
        'matches' => [['match_id' => $match->id, 'planned_at' => '2026-11-14T23:50', 'notes' => 'Keep the cue']],
    ]);

    // Assert
    $response->assertSessionHasNoErrors();

    // Act
    match ($change) {
        'date' => $event->update(['date' => now()->addWeek()]),
        'timezone' => $promotion->update(['timezone' => 'America/Chicago']),
        'competitor' => $wrestler->update(['name' => 'Harbor Ace II']),
        default => throw new LogicException('Unknown event-pack change fixture.'),
    };
    $page = get(route('events.pack.show', $event));

    // Assert
    expect($page->viewData('needsReview'))->toBeTrue()
        ->and(data_get($page->viewData('content'), 'timezone'))->toBe('America/New_York')
        ->and(data_get($page->viewData('content'), 'matches.0.planned_at'))->toBe('2026-11-14T23:50')
        ->and(data_get($page->viewData('content'), 'matches.0.notes'))->toBe('Keep the cue');
})->with(['date', 'timezone', 'competitor']);
