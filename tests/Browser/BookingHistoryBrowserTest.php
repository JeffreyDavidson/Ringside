<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;

it('checks deferred selections and removes the snapshot when the selections change', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = administrator();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $first = Wrestler::factory()->for($promotion, 'promotion')->bookable()->create(['name' => 'History First']);
    $second = Wrestler::factory()->for($promotion, 'promotion')->bookable()->create(['name' => 'History Second']);
    $past = Event::factory()->for($promotion, 'promotion')->past()->create(['name' => 'Recorded History Show']);
    $match = EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create();
    $match->update(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $match->sides()->firstOrFail()->id]);
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create(['name' => 'History Target Show']);

    // Act / Assert
    $page = visit(route('events.show', $event), ['reducedMotion' => 'reduce']);
    $page->resize(1440, 1000);
    $page->click('[data-test="add-event-match"]')
        ->assertVisible('select[name="form.matchType"]')
        ->select('select[name="form.matchType"]', MatchType::Singles->value)
        ->click('[data-roster-combobox="form.competitors.0.wrestlers.0"] input')
        ->type('[data-roster-combobox="form.competitors.0.wrestlers.0"] input', 'History First')
        ->click('[data-roster-combobox="form.competitors.0.wrestlers.0"] [role="option"]:has-text("History First")')
        ->click('[data-roster-combobox="form.competitors.1.wrestlers.0"] input')
        ->type('[data-roster-combobox="form.competitors.1.wrestlers.0"] input', 'History Second')
        ->click('[data-roster-combobox="form.competitors.1.wrestlers.0"] [role="option"]:has-text("History Second")')
        ->click('[data-test="check-history"]')
        ->assertSee('Direct head-to-head record')
        ->assertSee('Latest opposing date')
        ->click('[data-test="booking-history"] summary:has-text("History First")')
        ->assertSee('Recorded History Show')
        ->assertSee('History First by Pinfall')
        ->assertNoJavaScriptErrors();
    $page->script('document.querySelector("[data-test=booking-history]").scrollIntoView()');
    $page->screenshot(fullPage: false, filename: 'booking-history-desktop');
    $page->resize(390, 844);
    $page->script('document.querySelector("[data-test=booking-history]").scrollIntoView()');
    $page->screenshot(fullPage: false, filename: 'booking-history-phone');
    $page->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
    $page->script(<<<'JS'
        (() => {
            const originalFetch = window.fetch.bind(window);
            window.fetch = async (...args) => {
                const response = await originalFetch(...args);
                if (typeof args[1]?.body === 'string' && args[1].body.includes('"checkHistory"')) {
                    window.fetch = originalFetch;
                    await new Promise(resolve => { window.releaseHistory = resolve; });
                }
                return response;
            };
        })()
    JS);
    $page->click('[data-test="check-history"]');
    waitForScript($page, 'typeof window.releaseHistory === "function"');
    $page->select('select[name="form.matchType"]', MatchType::TagTeam->value);
    $page->assertDontSee('Direct head-to-head record')->assertNoJavaScriptErrors();
    $page->script('window.releaseHistory()');
    waitForScript($page, <<<'JS'
        document.querySelector('select[name="form.matchType"]').value === 'tag-team' && !document.querySelector('[data-test="check-history"]').disabled
    JS);
    $page->assertDontSee('Direct head-to-head record')->assertNoJavaScriptErrors();
    expect($event->matches()->count())->toBe(0);
});

it('reads and clears opposing history on a phone without a wide table', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = administrator();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $first = Wrestler::factory()->for($promotion, 'promotion')->create(['name' => 'Profile History First']);
    $second = Wrestler::factory()->for($promotion, 'promotion')->create(['name' => 'Profile History Second']);
    $event = Event::factory()->for($promotion, 'promotion')->past()->create(['name' => 'Profile History Show']);
    EventMatch::factory()->for($event)->withCompetitors([$first, $second])->create(['match_finish' => MatchFinish::NoDecision]);

    // Act / Assert
    $page = visit(route('wrestlers.show', [$first, 'opponent' => 'wrestler:'.$second->id]), ['reducedMotion' => 'reduce']);
    $page->resize(1440, 1000)->assertSee('Only matches against Profile History Second')
        ->assertSee('Profile History Show')->assertSee('No Decision')->assertNoJavaScriptErrors();
    $page->screenshot(fullPage: false, filename: 'opponent-history-desktop');
    $page->resize(390, 844);
    $page->script('document.querySelector("[data-test=resource-table-scroll]").scrollIntoView({block: "start"})');
    $page->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(fullPage: false, filename: 'opponent-history-phone')
        ->click('Show every opponent')->assertDontSee('Only matches against Profile History Second')
        ->assertSee('Profile History Show')->assertNoJavaScriptErrors();
});
