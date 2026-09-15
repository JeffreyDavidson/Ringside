<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

test('administrator can book a singles match through the event page', function (): void {
    $event = Event::factory()->scheduled()->withVenue()->create();
    $referee = Referee::factory()->bookable()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create(['name' => 'First Browser Competitor']);
    $secondWrestler = Wrestler::factory()->bookable()->create(['name' => 'Second Browser Competitor']);

    $this->actingAs(administrator());

    $page = visit(route('events.show', $event));

    $page->click('@add-event-match')
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
