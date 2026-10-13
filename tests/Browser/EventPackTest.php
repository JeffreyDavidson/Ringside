<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;

use function Pest\Laravel\actingAs;

test('an operator saves a segment reopens the pack and selects print sections', function (): void {
    $event = Event::factory()->scheduled()->create(['name' => 'Harbor Showcase']);
    EventMatch::factory()->for($event)->singles()->withReferees()->create();
    actingAs(administrator());

    $page = visit(route('events.pack.show', $event));
    $page->resize(390, 844)
        ->assertSee('Harbor Showcase')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->type('Master-copy holder', 'Morgan Ellis')
        ->type('Show notes', 'Check the replacement before doors.')
        ->assertSee('Unsaved changes')
        ->click('Add segment')
        ->type('Segment name', 'Intermission')
        ->click('Save pack')
        ->assertSee('Event pack saved.')
        ->assertValue('master', 'Morgan Ellis')
        ->assertValue('notes', 'Check the replacement before doors.')
        ->assertScript('document.querySelectorAll("[data-segments] [data-segment]").length === 1')
        ->screenshot(fullPage: false, filename: 'event-pack-mobile')
        ->resize(1440, 1000)
        ->screenshot(fullPage: false, filename: 'event-pack-desktop')
        ->click('a[href="#running-order"]')
        ->assertScript('document.querySelector(".pack-savebar").getBoundingClientRect().bottom <= innerHeight')
        ->screenshot(fullPage: false, filename: 'event-pack-running-order')
        ->click('a[href="#pack-notes"]')
        ->screenshot(fullPage: false, filename: 'event-pack-notes')
        ->type('Show notes', 'Unsaved draft')
        ->assertScript('(() => { const event = new Event("beforeunload", {cancelable: true}); window.dispatchEvent(event); return event.defaultPrevented; })()')
        ->script('window.confirm = () => false');
    $page->click('Preview saved pack')
        ->assertValue('notes', 'Unsaved draft')
        ->script('window.confirm = () => true');
    $page->click('Preview saved pack')
        ->assertSee('Intermission')
        ->assertSee('Check the replacement before doors.')
        ->uncheck('[data-print-section="roster"]')
        ->assertScript('document.querySelector("[data-section=roster]").hidden')
        ->check('[data-print-section="roster"]')
        ->assertScript('!document.querySelector("[data-section=roster]").hidden')
        ->screenshot(fullPage: true, filename: 'event-pack-print')
        ->assertNoJavascriptErrors();
});
