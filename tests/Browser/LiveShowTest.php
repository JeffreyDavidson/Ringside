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
    $stale->click('Start item')->assertSee('This show changed on another screen.')->assertNoJavaScriptErrors();
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
