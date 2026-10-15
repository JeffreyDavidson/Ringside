<?php

declare(strict_types=1);

use App\Actions\Events\SaveEventPackAction;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ViewModels\EventPackViewModel;

use function Pest\Laravel\actingAs;

require_once __DIR__.'/../Fixtures/live-show.php';

test('an operator prepares checks in runs and completes a show on mobile', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['name' => 'Harbor Showcase', 'date' => now()]);
    $wrestlers = Wrestler::factory()->count(2)->bookable()->create();
    EventMatch::factory()->for($event)->withCompetitors($wrestlers->all())->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], $data['content'], false);

    // Act / Assert
    $page = visit(route('events.live-show.show', $event));
    $page->resize(390, 844)
        ->click('Prepare live show')
        ->assertSee('Roster checklist')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->click('article:first-child button:first-child')
        ->assertSee('Clear arrival')
        ->click('article:first-child button:nth-child(2)')
        ->assertSee('Clear briefing')
        ->click('Start item')
        ->assertSee('Finish item')
        ->script('document.getElementById("main-content").scrollTop = 0');
    $page->screenshot(fullPage: false, filename: 'live-show-mobile')
        ->resize(1440, 1000)
        ->script('document.getElementById("main-content").scrollTop = 0');
    $page->screenshot(fullPage: false, filename: 'live-show-desktop')
        ->click('Finish item')
        ->assertSee('Finished')
        ->script('window.confirm = () => true');
    $page->click('Complete show')
        ->assertSee('Show completed')
        ->assertDontSee('Start item')
        ->assertNoJavaScriptErrors();
    expect(EventLiveRun::query()->where('event_id', $event->id)->firstOrFail()->completed_at)->not->toBeNull();
});

test('a correction identifies the item and brings its reason field into view', function (): void {
    // Arrange
    $fixture = liveShowFixture();

    // Act / Assert
    $page = visit(route('events.live-show.show', $fixture['event']));
    $page->resize(390, 844)->click('Skip item')
        ->assertSee($fixture['run']->content['items'][0]['label'])
        ->assertScript('document.activeElement.getAttribute("wire:model") === "reason"')
        ->assertScript('document.activeElement.getBoundingClientRect().top >= 64 && document.activeElement.getBoundingClientRect().bottom < innerHeight - 58')
        ->type('Reason', 'Promo replaces this match')
        ->click('Save change')
        ->assertSee('Skipped')->assertSee('Promo replaces this match')
        ->assertNoJavaScriptErrors();
});

test('two browser screens do not overwrite the first saved start', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $first = visit(route('events.live-show.show', $fixture['event']));
    $stale = visit(route('events.live-show.show', $fixture['event']));

    $first->assertSee('Start item');
    $stale->assertSee('Start item');

    // Act / Assert
    $first->click('Start item')->assertSee('Finish item');
    $stale->click('Start item')->assertSee('This was changed by someone else.')->assertNoJavaScriptErrors();
    expect($fixture['run']->refresh()->version)->toBe(2);
});

test('a failed request preserves confirmed state and refresh enables a safe retry', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $page = visit(route('events.live-show.show', $fixture['event']));
    $page->script('function () { window.liveOriginalFetch = window.fetch; window.fetch = (...args) => { window.fetch = window.liveOriginalFetch; return Promise.reject(new TypeError("Simulated connection failure")); }; return true; }');

    // Act / Assert
    $page->click('Start item')->assertSee('Connection or request failed.')
        ->assertScript('document.querySelector("section[aria-labelledby=live-next] fieldset").disabled');
    expect($fixture['run']->refresh()->version)->toBe(1);
    $page->click('Refresh')->assertDontSee('Connection or request failed.')
        ->click('Start item')->assertSee('Finish item')->assertNoJavaScriptErrors();
});

test('phone show navigation keeps status visible and returns to the primary controls', function (): void {
    // Arrange
    $fixture = liveShowFixture(segment: true);
    $page = visit(route('events.live-show.show', $fixture['event']));

    // Act / Assert
    $page->resize(390, 844)->click('Start item')->assertSee('Finish item')
        ->assertScript('document.querySelector("section[aria-labelledby=live-next] button").matches(":disabled")')
        ->assertScript('document.querySelector("section[aria-labelledby=live-next]").getBoundingClientRect().bottom < innerHeight - 58')
        ->screenshot(fullPage: false, filename: 'live-show-phone-refined')
        ->resize(1440, 1000)->screenshot(fullPage: false, filename: 'live-show-desktop-refined')
        ->resize(390, 844)->click('a[href="#live-roster"]')
        ->assertScript('document.getElementById("live-roster").getBoundingClientRect().top >= document.querySelector("[data-live-toolbar]").getBoundingClientRect().bottom')
        ->assertScript('document.querySelector("[data-live-toolbar]").getBoundingClientRect().top >= 64 && document.querySelector("[data-live-toolbar]").getBoundingClientRect().top < 70')
        ->click('article:first-child button:first-child')
        ->assertSee('1 / 3 arrived')
        ->screenshot(fullPage: false, filename: 'live-show-roster-refined')
        ->click('a[href="#live-order"]')
        ->assertScript('document.getElementById("live-order").getBoundingClientRect().top >= document.querySelector("[data-live-toolbar]").getBoundingClientRect().bottom')
        ->click('a[href="#live-show-controls"]')
        ->assertScript('document.querySelector("section[aria-labelledby=live-now]").getBoundingClientRect().top >= document.querySelector("[data-live-toolbar]").getBoundingClientRect().bottom')
        ->resize(320, 700)->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->resize(844, 390)->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->script('window.dispatchEvent(new Event("offline"))');
    $page->assertSee('Offline — changes cannot be saved.')
        ->assertScript('document.querySelector("[data-live-toolbar]").innerText.includes("Offline") && !document.querySelector("[data-live-toolbar]").innerText.includes("Online")')
        ->script('window.dispatchEvent(new Event("online"))');
    $page->assertDontSee('Offline — changes cannot be saved.')
        ->click('Finish item')
        ->assertScript('!document.querySelector("section[aria-labelledby=live-next] button").matches(":disabled")')
        ->click('Start item')->assertSee('Finish item')->assertNoJavaScriptErrors();
});
