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
    Referee::factory()->bookable()->create(['first_name' => 'Rowdy', 'last_name' => 'Official']);
    Wrestler::factory()->bookable()->create(['name' => 'First Browser Competitor']);
    Wrestler::factory()->bookable()->create(['name' => 'Second Browser Competitor']);

    $this->actingAs(administrator());

    $page = visit(route('events.show', $event));

    $page->assertSee('Add Event Match');
    $page->script("Livewire.dispatch('openModal', { component: 'matches.modals.form-modal', arguments: { eventId: {$event->id} } })");
    waitForModalReady($page);

    $page
        ->waitForText('Add Match')
        ->select('select[name="form.matchType"]', MatchType::Singles->value)
        ->click('input[data-field="form.competitors.0.wrestlers.0"]')
        ->typeSlowly('input[data-field="form.competitors.0.wrestlers.0"]', 'First Browser', 20)
        ->click('[data-roster-combobox="form.competitors.0.wrestlers.0"] [role="option"]:has-text("First Browser Competitor")')
        ->assertValue('input[data-field="form.competitors.0.wrestlers.0"]', 'First Browser Competitor')
        ->click('input[data-field="form.competitors.1.wrestlers.0"]')
        ->typeSlowly('input[data-field="form.competitors.1.wrestlers.0"]', 'second browser', 20)
        ->click('[data-roster-combobox="form.competitors.1.wrestlers.0"] [role="option"]:has-text("Second Browser Competitor")')
        ->assertValue('input[data-field="form.competitors.1.wrestlers.0"]', 'Second Browser Competitor')
        ->click('input[data-field="form.referees"]')
        ->typeSlowly('input[data-field="form.referees"]', 'Rowdy', 20)
        ->click('[data-roster-combobox="form.referees"] [role="option"]:has-text("Rowdy Official")');
    waitForScript($page, 'document.querySelector(\'[data-roster-combobox="form.referees"] [data-test="selected-chips"]\').textContent.includes("Rowdy Official")');
    $page->press('Save');
    waitForModalToClose($page);
    $page
        ->assertSee('First Browser Competitor')
        ->assertSee('Second Browser Competitor')
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
        ->click('[aria-label="More actions for match '.$match->match_number.'"]')
        ->assertSee('Edit Match')
        ->click('[data-test="match-edit-action"]')
        ->assertValue('select[name="form.matchType"]', MatchType::Singles->value)
        ->assertValue('input[data-field="form.competitors.1.wrestlers.0"]', 'Original Edit Opponent');
    waitForModalReady($page);
    $page
        ->click('input[data-field="form.competitors.1.wrestlers.0"]')
        ->typeSlowly('input[data-field="form.competitors.1.wrestlers.0"]', 'Replacement', 20)
        ->click('[data-roster-combobox="form.competitors.1.wrestlers.0"] [role="option"]:has-text("Replacement Edit Opponent")')
        ->assertValue('input[data-field="form.competitors.1.wrestlers.0"]', 'Replacement Edit Opponent')
        ->fill('textarea[name="form.preview"]', 'The challenger steps into the spotlight.')
        ->press('Save');
    waitForScript($page, '! document.querySelector("#modal-container").checkVisibility()');
    $page
        ->assertSee('Replacement Edit Opponent')
        ->assertNoJavascriptErrors();

    expect($match->refresh()->preview)->toBe('The challenger steps into the spotlight.')
        ->and($match->wrestlers()->pluck('wrestlers.id')->all())
        ->toBe([$firstWrestler->id, $replacementOpponent->id]);
});

test('administrator can remove a match from the event page', function (): void {
    $event = Event::factory()->scheduled()->withVenue()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create(['name' => 'First Removal Competitor']);
    $secondWrestler = Wrestler::factory()->bookable()->create(['name' => 'Second Removal Competitor']);
    $match = EventMatch::factory()
        ->for($event)
        ->withCompetitors([$firstWrestler, $secondWrestler])
        ->create(['match_type' => MatchType::Singles]);

    $this->actingAs(administrator());

    $page = visit(route('events.show', $event));

    $page->assertSee('First Removal Competitor');
    $page->script('window.confirm = () => true');
    $page
        ->click('[aria-label="More actions for match '.$match->match_number.'"]')
        ->click('[data-test="match-delete-action"]')
        ->waitForText('Match successfully deleted.')
        ->assertDontSee('First Removal Competitor')
        ->assertNoJavascriptErrors();

    expect($match->refresh()->trashed())->toBeTrue();
});

test('match form layouts adapt to narrow screens and keep multiple selections usable', function (): void {
    $event = Event::factory()->scheduled()->withVenue()->create();
    Wrestler::factory()->bookable()->create(['name' => 'Responsive Multi Competitor']);

    $this->actingAs(administrator());

    $page = visit(route('events.show', $event));

    $page->resize(390, 844);
    $page->script("Livewire.dispatch('openModal', { component: 'matches.modals.form-modal', arguments: { eventId: {$event->id} } })");
    waitForModalReady($page);

    $page
        ->waitForText('Add Match')
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
        ->click('input[data-field="form.competitors.0.wrestlers"]')
        ->typeSlowly('input[data-field="form.competitors.0.wrestlers"]', 'Responsive', 20)
        ->click('[data-roster-combobox="form.competitors.0.wrestlers"] [role="option"]:has-text("Responsive Multi Competitor")')
        ->assertSeeIn('[data-roster-combobox="form.competitors.0.wrestlers"] [data-test="selected-chips"]', 'Responsive Multi Competitor')
        ->assertVisible('input[data-field="form.competitors.0.wrestlers"]')
        ->assertVisible('input[data-field="form.competitors.0.tag_teams"]')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
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
        ->assertSeeIn('#modal-title', 'Add Event')
        ->assertAttribute('input[name="form.date"]', 'type', 'datetime-local')
        ->fill('input[name="form.name"]', 'Night of Champions')
        ->fill('input[name="form.date"]', $eventDate->format('Y-m-d\\TH:i'))
        ->select('select[name="form.venue_id"]', (string) $venue->id)
        ->press('Save')
        ->assertSee('Night of Champions')
        ->assertNoJavascriptErrors();

    waitForModalToClose($page);

    $event = Event::query()->whereName('Night of Champions')->firstOrFail();
    expect($event->date?->toDateTimeString())->toBe($eventDate->toDateTimeString());

    $updatedDate = $eventDate->copy()->addHour();
    $page
        ->click('button[aria-label="Actions for Night of Champions"]')
        ->click('tr:has-text("Night of Champions") [data-row-actions-panel] button:has-text("Edit")')
        ->assertPathIs('/events')
        ->assertSeeIn('#modal-title', 'Edit Night of Champions')
        ->assertValue('input[name="form.date"]', $eventDate->format('Y-m-d\\TH:i'))
        ->fill('input[name="form.date"]', $updatedDate->format('Y-m-d\\TH:i'))
        ->press('Save')
        ->assertDontSee('Edit Night of Champions')
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
        ->assertSeeIn('#modal-title', 'Add Event')
        ->fill('input[name="form.name"]', 'Second Night at the Venue')
        ->fill('input[name="form.date"]', $conflictingDate->format('Y-m-d\\TH:i'))
        ->select('select[name="form.venue_id"]', (string) $venue->id)
        ->press('Save')
        ->assertSee("Venue [{$venue->name}] is already booked on {$conflictingDate->format('M j, Y')} (venue time).")
        ->assertAttribute('select[name="form.venue_id"]', 'aria-invalid', 'true')
        ->assertSeeIn('#modal-title', 'Add Event');

    expect(Event::query()->count())->toBe(1);

    $availableDate = $conflictingDate->copy()->addDay();
    $page
        ->fill('input[name="form.date"]', $availableDate->format('Y-m-d\\TH:i'))
        ->press('Save')
        ->assertSee('Second Night at the Venue')
        ->assertMissing('#modal-title')
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
        ->assertSeeIn('#modal-title', 'Edit Original Browser Event')
        ->fill('input[name="form.name"]', 'Rescheduled Browser Event')
        ->select('select[name="form.venue_id"]', (string) $conflictingVenue->id)
        ->press('Save')
        ->assertSee("Venue [{$conflictingVenue->name}] is already booked on {$conflictingDate->format('M j, Y')} (venue time).")
        ->assertAttribute('select[name="form.venue_id"]', 'aria-invalid', 'true')
        ->assertSeeIn('#modal-title', 'Edit Original Browser Event');

    expect($event->refresh()->name)->toBe('Original Browser Event')
        ->and($event->date?->toDateTimeString())->toBe($conflictingDate->toDateTimeString())
        ->and($event->venue_id)->toBe($originalVenue->id);

    $availableDate = $conflictingDate->copy()->addDay();
    $page
        ->fill('input[name="form.date"]', $availableDate->format('Y-m-d\\TH:i'))
        ->press('Save')
        ->assertSee('Rescheduled Browser Event')
        ->assertDontSee('Edit Rescheduled Browser Event')
        ->assertNoJavascriptErrors();

    expect($event->refresh()->name)->toBe('Rescheduled Browser Event')
        ->and($event->date?->toDateTimeString())->toBe($availableDate->toDateTimeString())
        ->and($event->venue_id)->toBe($conflictingVenue->id);
});
