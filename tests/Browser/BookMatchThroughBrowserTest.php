<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Models\Events\Event;
use App\Models\Events\Venue;
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
        ->assertNoJavascriptErrors();

    expect($event->matches()->count())->toBe(1);
});

test('administrator can create and edit an event with a showtime', function (): void {
    $venue = Venue::factory()->create();
    $this->actingAs(administrator());
    $eventDate = now()->addDays(30)->setTime(19, 45)->startOfMinute();

    $page = visit(route('events.index'));

    $page
        ->click('Add Event')
        ->assertSee('Create Event')
        ->assertAttribute('input[name="form.date"]', 'type', 'datetime-local')
        ->fill('input[name="form.name"]', 'Night of Champions')
        ->fill('input[name="form.date"]', $eventDate->format('Y-m-d\\TH:i'))
        ->select('select[name="form.venue_id"]', (string) $venue->id)
        ->press('Save')
        ->assertSee('Night of Champions')
        ->assertNoJavascriptErrors();

    $event = Event::query()->whereName('Night of Champions')->firstOrFail();
    expect($event->date?->toDateTimeString())->toBe($eventDate->toDateTimeString());

    $updatedDate = $eventDate->copy()->addHour();
    $page
        ->click('button[aria-label="Actions for Night of Champions"]')
        ->click('[role="menuitem"]:has-text("Edit")')
        ->assertSee('Edit Event')
        ->assertValue('input[name="form.date"]', $eventDate->format('Y-m-d\\TH:i'))
        ->fill('input[name="form.date"]', $updatedDate->format('Y-m-d\\TH:i'))
        ->press('Save')
        ->assertDontSee('Edit Event')
        ->assertNoJavascriptErrors();

    expect($event->refresh()->date?->toDateTimeString())->toBe($updatedDate->toDateTimeString());
});
