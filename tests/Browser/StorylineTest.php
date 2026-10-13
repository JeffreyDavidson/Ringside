<?php

declare(strict_types=1);
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;

use function Pest\Laravel\actingAs;

test('a promoter plans several shows and records a different outcome on mobile', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create(['name' => 'Harbor Wrestling']);
    // Act / Assert
    $page = visit(route('storylines.index'));
    $page->resize(390, 844)->click('New storyline')
        ->select('select[name="promotionId"]', (string) $promotion->id)
        ->type('input[name="title"]', 'Partners become rivals')
        ->type('textarea[name="premise"]', 'A friendship breaks down across three shows.')
        ->type('textarea[name="payoff"]', 'A title match after the final confrontation.')
        ->press('Create storyline')
        ->assertSee('Last happened')->assertSee('Next planned')
        ->click('Add step')
        ->type('input[name="form.heading"]', 'The betrayal')
        ->type('textarea[name="form.description"]', 'Turn on the partner after the main event.')
        ->press('Save changes')->assertSee('The betrayal')
        ->click('Add step')
        ->type('input[name="form.heading"]', 'The title-match payoff')
        ->type('textarea[name="form.description"]', 'The rivals finally meet for the championship.')
        ->type('input[name="form.targetOn"]', now()->addDays(21)->toDateString())
        ->press('Save changes')->assertSee('The title-match payoff');
    $page->click('Record what happened')
        ->type('input[name="form.heading"]', 'A warning instead')
        ->type('textarea[name="form.description"]', 'They argued but stayed together for one more show.')
        ->type('input[name="form.occurredOn"]', today()->toDateString())
        ->press('Save changes')->assertSee('A warning instead')
        ->assertSee('The title-match payoff')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->script('document.getElementById("main-content").scrollTop = 0');
    $page->screenshot(fullPage: false, filename: 'storyline-mobile')
        ->resize(1440, 1000)
        ->script('document.getElementById("main-content").scrollTop = 0');
    $page->screenshot(fullPage: false, filename: 'storyline-desktop')
        ->assertNoJavaScriptErrors();
    expect(Storyline::query()->firstOrFail()->developments()->count())->toBe(1);
});

require_once __DIR__.'/../Fixtures/storylines.php';
test('a phone booker selects creative talent and links an existing segment with usable touch targets', function (): void {
    $f = storylineSourceFixture();
    $wrestler = Wrestler::factory()->for(requiredModel($f['story']->promotion))->create(['name' => 'Harbor Creative']);
    $page = visit(route('storylines.show', $f['story']));
    $page->resize(390, 844)->click('Add participant')
        ->click('[data-roster-combobox="form.participantId"] input')
        ->typeSlowly('[data-roster-combobox="form.participantId"] input', 'Harbor Creative', 20);
    waitForScript($page, 'document.querySelectorAll("[data-roster-combobox] [role=option]").length === 1');
    $page->click('[data-roster-combobox="form.participantId"] [role="option"]:has-text("Harbor Creative")')
        ->type('input[name="form.role"]', 'Rival')
        ->press('Save changes')->assertSee('Harbor Creative')
        ->click('Add step')
        ->type('input[name="form.heading"]', 'Closing confrontation')
        ->type('textarea[name="form.description"]', 'The rivals argue after the main event.')
        ->select('select[name="form.eventId"]', (string) $f['event']->id)
        ->select('select[name="form.segmentId"]', $f['segment'])
        ->assertSee('Closing promo');
    $page->assertScript('[...document.querySelectorAll("#storyline-form input:not([type=hidden]), #storyline-form select, #storyline-form button")].filter(e => e.checkVisibility()).every(e => e.getBoundingClientRect().height >= 44)')
        ->screenshot(fullPage: false, filename: 'storyline-source-form-mobile')
        ->press('Save changes')->assertSee('Closing confrontation')->assertNoJavaScriptErrors();
});
