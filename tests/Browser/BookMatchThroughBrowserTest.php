<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

test('administrator can book a singles match through the event page', function (): void {
    $event = Event::factory()->scheduled()->withVenue()->create();
    $referee = Referee::factory()->bookable()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create(['name' => 'First Browser Competitor']);
    $secondWrestler = Wrestler::factory()->bookable()->create(['name' => 'Second Browser Competitor']);

    $this->actingAs(administrator());

    $page = visit(route('events.show', $event));

    $page->assertSee('Add Event Match');
    $page->script("Livewire.dispatch('openModal', { component: 'matches.modals.form-modal', arguments: { eventId: {$event->id} } })");

    $page
        ->waitForText('Create Match')
        ->select('select[name="form.matchType"]', MatchType::Singles->value)
        ->select('select[name="form.competitors.0.wrestlers.0"]', (string) $firstWrestler->id)
        ->select('select[name="form.competitors.1.wrestlers.0"]', (string) $secondWrestler->id)
        ->select('select[name="form.referees[]"]', [(string) $referee->id])
        ->press('Save')
        ->waitForText('First Browser Competitor')
        ->assertSee('Second Browser Competitor')
        ->assertScript('!document.querySelector("#modal-container").checkVisibility()')
        ->wait(0.35)
        ->assertNoJavascriptErrors();

    expect($event->matches()->count())->toBe(1);
});

test('administrator can edit an unresulted match from the event page', function (): void {
    $event = Event::factory()->scheduled()->withVenue()->create();
    $referee = Referee::factory()->bookable()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create(['name' => 'First Edit Competitor']);
    $originalOpponent = Wrestler::factory()->bookable()->create(['name' => 'Original Edit Opponent']);
    $replacementOpponent = Wrestler::factory()->bookable()->create(['name' => 'Replacement Edit Opponent']);
    $match = EventMatch::factory()
        ->for($event)
        ->withCompetitors([$firstWrestler, $originalOpponent])
        ->create([
            'match_type' => MatchType::Singles,
            'preview' => 'The original match preview.',
        ]);
    $match->referees()->attach($referee);

    $this->actingAs(administrator());

    $page = visit(route('events.show', $event));

    $page->assertSee('Add Event Match');
    $page
        ->assertSee('Edit Match')
        ->click('[data-test="match-edit-action"]')
        ->assertValue('select[name="form.matchType"]', MatchType::Singles->value)
        ->select('select[name="form.competitors.1.wrestlers.0"]', (string) $replacementOpponent->id)
        ->fill('textarea[name="form.preview"]', 'The challenger steps into the spotlight.')
        ->press('Save')
        ->assertSee('Replacement Edit Opponent')
        ->assertNoJavascriptErrors();

    expect($match->refresh()->preview)->toBe('The challenger steps into the spotlight.')
        ->and($match->wrestlers()->pluck('wrestlers.id')->all())
        ->toBe([$firstWrestler->id, $replacementOpponent->id]);
});

test('match form layouts adapt to narrow screens and keep multiple selections usable', function (): void {
    $event = Event::factory()->scheduled()->withVenue()->create();

    $this->actingAs(administrator());

    $page = visit(route('events.show', $event));

    $page->resize(390, 844);
    $page->script("Livewire.dispatch('openModal', { component: 'matches.modals.form-modal', arguments: { eventId: {$event->id} } })");

    $page
        ->waitForText('Create Match')
        ->select('select[name="form.matchType"]', MatchType::TripleThreat->value)
        ->waitForText('Competitor 3')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=match-setup-grid]")).gridTemplateColumns.split(" ").length === 1')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=match-competitors-grid]")).gridTemplateColumns.split(" ").length === 1')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->resize(1440, 1000)
        ->assertScript('getComputedStyle(document.querySelector("[data-test=match-setup-grid]")).gridTemplateColumns.split(" ").length === 2')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=match-competitors-grid]")).gridTemplateColumns.split(" ").length === 3')
        ->select('select[name="form.matchType"]', MatchType::TagTeam->value)
        ->waitForText('Team A')
        ->assertScript('document.querySelector("select[name=\"form.competitors.0.wrestlers[]\"]").getBoundingClientRect().height >= 112')
        ->assertNoJavascriptErrors();
});

test('administrator can create and edit an event with a showtime', function (): void {
    $venue = Venue::factory()->create();
    $this->actingAs(administrator());
    $eventDate = now()->addDays(30)->setTime(19, 45)->startOfMinute();

    $page = visit(route('events.index'));

    $page
        ->click('Add Event')
        ->assertPathIs('/events')
        ->assertSee('Create Event')
        ->assertAttribute('input[name="form.date"]', 'type', 'datetime-local')
        ->fill('input[name="form.name"]', 'Night of Champions')
        ->fill('input[name="form.date"]', $eventDate->format('Y-m-d\\TH:i'))
        ->select('select[name="form.venue_id"]', (string) $venue->id)
        ->press('Save')
        ->assertSee('Night of Champions')
        ->assertNoJavascriptErrors();

    $page->wait(0.35);

    $event = Event::query()->whereName('Night of Champions')->firstOrFail();
    expect($event->date?->toDateTimeString())->toBe($eventDate->toDateTimeString());

    $updatedDate = $eventDate->copy()->addHour();
    $page
        ->click('button[aria-label="Actions for Night of Champions"]')
        ->click('tr:has-text("Night of Champions") [data-row-actions-panel] button:has-text("Edit")')
        ->assertPathIs('/events')
        ->assertSee('Edit Event')
        ->assertValue('input[name="form.date"]', $eventDate->format('Y-m-d\\TH:i'))
        ->fill('input[name="form.date"]', $updatedDate->format('Y-m-d\\TH:i'))
        ->press('Save')
        ->assertDontSee('Edit Event')
        ->assertNoJavascriptErrors();

    expect($event->refresh()->date?->toDateTimeString())->toBe($updatedDate->toDateTimeString());
});

test('administrator can recover from a venue scheduling conflict in the event form', function (): void {
    $venue = Venue::factory()->create();
    $conflictingDate = now()->addDays(30)->setTime(19, 45)->startOfMinute();
    Event::factory()->for($venue)->create(['date' => $conflictingDate]);

    $this->actingAs(administrator());

    $page = visit(route('events.index'));

    $page
        ->click('Add Event')
        ->assertSee('Create Event')
        ->fill('input[name="form.name"]', 'Second Night at the Venue')
        ->fill('input[name="form.date"]', $conflictingDate->format('Y-m-d\\TH:i'))
        ->select('select[name="form.venue_id"]', (string) $venue->id)
        ->press('Save')
        ->assertSee("Venue [{$venue->name}] is already booked at this event time.")
        ->assertAttribute('select[name="form.venue_id"]', 'aria-invalid', 'true')
        ->assertSee('Create Event');

    expect(Event::query()->count())->toBe(1);

    $availableDate = $conflictingDate->copy()->addHour();
    $page
        ->fill('input[name="form.date"]', $availableDate->format('Y-m-d\\TH:i'))
        ->press('Save')
        ->assertSee('Second Night at the Venue')
        ->assertDontSee('Create Event')
        ->assertNoJavascriptErrors();

    $event = Event::query()->whereName('Second Night at the Venue')->firstOrFail();
    expect($event->date?->toDateTimeString())->toBe($availableDate->toDateTimeString())
        ->and($event->venue_id)->toBe($venue->id);
});

test('administrator can recover from a venue scheduling conflict while editing an event', function (): void {
    $originalVenue = Venue::factory()->create();
    $conflictingVenue = Venue::factory()->create();
    $conflictingDate = now()->addDays(30)->setTime(19, 45)->startOfMinute();
    $event = Event::factory()->for($originalVenue)->create([
        'name' => 'Original Browser Event',
        'date' => $conflictingDate,
    ]);
    Event::factory()->for($conflictingVenue)->create(['date' => $conflictingDate]);

    $this->actingAs(administrator());

    $page = visit(route('events.index'));

    $page
        ->click('button[aria-label="Actions for Original Browser Event"]')
        ->click('tr:has-text("Original Browser Event") [data-row-actions-panel] button:has-text("Edit")')
        ->assertSee('Edit Event')
        ->fill('input[name="form.name"]', 'Rescheduled Browser Event')
        ->select('select[name="form.venue_id"]', (string) $conflictingVenue->id)
        ->press('Save')
        ->assertSee("Venue [{$conflictingVenue->name}] is already booked at this event time.")
        ->assertAttribute('select[name="form.venue_id"]', 'aria-invalid', 'true')
        ->assertSee('Edit Event');

    expect($event->refresh()->name)->toBe('Original Browser Event')
        ->and($event->date?->toDateTimeString())->toBe($conflictingDate->toDateTimeString())
        ->and($event->venue_id)->toBe($originalVenue->id);

    $availableDate = $conflictingDate->copy()->addHour();
    $page
        ->fill('input[name="form.date"]', $availableDate->format('Y-m-d\\TH:i'))
        ->press('Save')
        ->assertSee('Rescheduled Browser Event')
        ->assertDontSee('Edit Event')
        ->assertNoJavascriptErrors();

    expect($event->refresh()->name)->toBe('Rescheduled Browser Event')
        ->and($event->date?->toDateTimeString())->toBe($availableDate->toDateTimeString())
        ->and($event->venue_id)->toBe($conflictingVenue->id);
});
