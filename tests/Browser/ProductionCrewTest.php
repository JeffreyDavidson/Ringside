<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;

test('a manager coordinates crew and cues then uses phone live and print sheets', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    $event = Event::factory()->for($promotion)->create(['name' => 'Harbor Showcase', 'date' => now()]);
    $wrestlers = Wrestler::factory()->count(2)->for($promotion)->bookable()->create();
    $match = EventMatch::factory()->for($event)->withCompetitors($wrestlers->all())->create();
    $referee = Referee::factory()->for($promotion)->bookable()->create();
    $match->referees()->attach($referee);
    actingAs($user);

    // Act / Assert
    $directory = visit(route('crew.contacts.index'));
    $directory->resize(390, 844)->click('Add crew contact')->type('Name', 'Alex Operator')
        ->type('Telephone', '555-0100')->type('Private contact notes', 'Private transport detail')
        ->click('Save contact')->assertSee('Alex Operator')->assertNoJavaScriptErrors();
    $contact = CrewContact::query()->sole();
    $page = visit(route('events.pack.show', $event));
    $page->resize(390, 844)->click('a[href="#production"]')->click('Assign crew role')
        ->select('[wire\\:model="assignments.0.contact_id"]', (string) $contact->id)
        ->select('[wire\\:model="assignments.0.role"]', 'sound')
        ->type('Station / Other role description', 'Mixer')
        ->type('Setup duty', 'Test entrance levels')->type('Strike duty', 'Pack mixer and cables')
        ->click('#production details summary')->click('Add cue')
        ->type('Cue subject / performer', 'Harbor Hero')->type('Trigger', 'On caller signal')
        ->type('Instruction', 'Play Harbor theme, then fade under the announcement.')
        ->type('Entrance track title / filename', 'harbor-theme.mp3')
        ->click('Save crew & cues')->assertSee('Crew and cues saved.')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->screenshot(fullPage: false, filename: 'production-editor-phone');
    $page->type('Show notes', 'Briefing after doors')->click('Save pack')->assertSee('Event pack saved.')
        ->assertNoJavaScriptErrors();
    expect(data_get($event->pack()->firstOrFail()->content, 'production.cues.0.instruction'))->toBe('Play Harbor theme, then fade under the announcement.');
    $live = visit(route('events.live-show.show', $event));
    $live->resize(390, 844)->click('Prepare live show')->assertSee('Alex Operator')->assertSee('Cues')
        ->screenshot(fullPage: false, filename: 'production-live-phone')->assertNoJavaScriptErrors();
    $sheet = visit(route('events.production-sheet.show', $event));
    $sheet->resize(390, 844)->assertSee('Live snapshot')->assertSee('harbor-theme.mp3')
        ->assertDontSee('Private transport detail')->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->screenshot(fullPage: true, filename: 'production-sheet-phone')
        ->resize(1440, 1000)->screenshot(fullPage: false, filename: 'production-sheet-desktop')
        ->click('Print cue sheet')->assertSee('Crew call sheet')->assertSee('Production cue sheet')
        ->assertDontSee('Private transport detail')->uncheck('[data-print-section="crew"]')
        ->assertScript('document.querySelector("[data-section=crew]").hidden')->check('[data-print-section="crew"]')
        ->screenshot(fullPage: true, filename: 'production-print')->assertNoJavaScriptErrors();
});
