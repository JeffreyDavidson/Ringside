<?php

declare(strict_types=1);

require_once __DIR__.'/../Fixtures/talent-booking.php';

use App\Enums\Events\TalentBookingStatus;
use App\Models\Events\EventTalentBooking;
use App\Models\Events\EventTalentBookingRevision;
use App\Models\Roster\Wrestlers\Wrestler;

test('a phone editor changes terms reconfirms cancels reopens and reads private history', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $row = '[data-person="wrestler:'.$fixture['person']->id.'"]';
    $page = visit(route('events.talent-bookings.show', $fixture['event']));

    // Act / Assert
    $page->resize(390, 844)->click($row.' button:first-of-type')->assertSee('Last agreed event details')
        ->type('#talent-fee', '0')->type('#talent-travel', 'Updated private travel')
        ->press('Save for reconfirmation')->assertSee('Reconfirmation needed');
    expect($fixture['booking']->refresh()->fee_minor)->toBe(0)->and($fixture['booking']->terms_need_review)->toBeTrue();
    $page->click($row.' button:first-of-type')->press('Record agreement')->assertSee('Booking saved.')->assertDontSee('Reconfirmation needed');
    $page->click($row.' button:first-of-type')->select('#talent-state', 'cancelled')->press('Save booking')
        ->assertSee('Add a reason')->type('#talent-reason', 'Cannot attend')->press('Save booking')->assertSee('Cancelled');
    $page->click($row.' button:first-of-type')->assertSee('Reopening starts at Proposed')->press('Save booking')->assertSee('Proposed');
    $page->click($row.' button:nth-of-type(2)')->assertSee('Cannot attend')->assertSee('Updated private travel')->assertNoJavaScriptErrors();
    expect(EventTalentBooking::query()->count())->toBe(1)->and(EventTalentBookingRevision::query()->count())->toBe(5);
});

test('the roster search adds a pre-card proposal without inventing confirmation', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $extra = Wrestler::factory()->create(['name' => 'Extra Cedar']);
    $page = visit(route('events.talent-bookings.show', $fixture['event']));

    // Act / Assert
    $page->click('[data-roster-combobox="selectedPerson"] input')->typeSlowly('[data-roster-combobox="selectedPerson"] input', 'Extra Cedar', 20)->assertSee('Extra Cedar')->click('[data-roster-combobox="selectedPerson"] [role="option"]:has-text("Extra Cedar")')
        ->press('Open booking')->assertSee('Agreed fee (USD)')->press('Save booking')->assertSee('Booking saved.')->assertSee('Proposed')->assertNoJavaScriptErrors();
    expect(EventTalentBooking::query()->where('person_id', $extra->id)->sole()->status)->toBe(TalentBookingStatus::Proposed);
});

test('booking warnings and controls fit phone and desktop viewports', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $fixture['person']->delete();
    $fixture['event']->update(['date' => now()->addDays(2)]);
    $page = visit(route('events.talent-bookings.show', $fixture['event']));

    // Act / Assert
    $page->resize(390, 844)->assertSee('Confirmed — unavailable')->assertSee('Reconfirmation needed')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')->screenshot(fullPage: false, filename: 'talent-bookings-phone');
    $page->resize(1440, 1000)->screenshot(fullPage: false, filename: 'talent-bookings-desktop')
        ->screenshotElement('#main-content', filename: 'talent-bookings-desktop-main');
    $page->resize(320, 780)->click('[data-person] button:first-of-type')->assertSee('Agreed fee (USD)')
        ->assertScript('document.getElementById("booking-editor-heading").getBoundingClientRect().top >= 64')
        ->assertScript('document.activeElement.id === "booking-editor-heading"')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')->screenshot(fullPage: false, filename: 'talent-bookings-editor-320')
        ->press('Close')->assertScript('document.activeElement.id.startsWith("talent-edit-")')
        ->click('[data-person] button:nth-of-type(2)')->assertScript('document.activeElement.id === "booking-editor-heading"')
        ->press('Close')->assertScript('document.activeElement.id.startsWith("talent-history-")')->assertNoJavaScriptErrors();
});
