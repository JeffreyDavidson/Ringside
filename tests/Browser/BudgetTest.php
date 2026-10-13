<?php

declare(strict_types=1);
use App\Actions\Events\RecordBudgetMovementAction;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;

use function Pest\Laravel\actingAs;

test('an operator plans a fee and records a deposit on a phone', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['name' => 'Harbor Budget Showcase']);
    // Act / Assert
    $page = visit(route('events.budget.show', $event));
    $page->resize(390, 844)->click('Add expense')->type('[name="lineForm.description"]', 'Main event fee')->type('[name="lineForm.counterparty"]', 'Casey')
        ->type('[name="lineForm.planned"]', '500.00')->type('[name="lineForm.confirmed"]', '500.00')->click('Save record')
        ->assertSee('Main event fee')->assertSee('500.00 USD')->click('Record payment / receipt')->type('[name="movementForm.amount"]', '200.00')->click('Save record')
        ->assertSee('300.00 USD')->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->script('document.getElementById("main-content").scrollTop = 0');
    $page->screenshot(fullPage: false, filename: 'budget-mobile')->resize(1440, 1000)
        ->script('document.getElementById("main-content").scrollTop = 0');
    $page->screenshot(fullPage: false, filename: 'budget-desktop')->assertNoJavaScriptErrors();
    expect(EventBudget::query()->firstOrFail()->lines()->firstOrFail()->movements()->count())->toBe(1);
});

require_once __DIR__.'/../Fixtures/budget.php';

test('a lost payment response requires refresh and replay never duplicates recorded money', function (): void {
    // Arrange
    $f = budgetFixture();
    $page = visit(route('events.budget.show', $f['event']));
    $page->click('Record payment / receipt')->type('[name="movementForm.amount"]', '200.00');
    // Simulate loss of the response after the server has committed the payment.
    $page->script('(() => { if (window.budgetOriginalFetch) return true; const original = window.fetch.bind(window); window.budgetOriginalFetch = original; window.fetch = async (...args) => { const response = await original(...args); if (String(args[0]).includes("/update")) throw new TypeError("Simulated lost response"); return response; }; return true; })()');
    // Act / Assert
    $page->click('Save record')->assertSee('The request outcome is uncertain. Refresh saved totals before retrying.');
    $page->script('(() => { window.fetch = window.budgetOriginalFetch; return true; })()');
    $page->click('Refresh saved totals; keep draft')->assertSee('300.00 USD')
        ->click('Save record')->assertSee('Record saved.')->assertNoJavaScriptErrors();
    expect($f['line']->movements()->count())->toBe(1)->and($f['budget']->revisions()->count())->toBe(1);
});

test('operators select a refund source and reconcile changed terms through the browser', function (): void {
    // Arrange
    $f = budgetFixture();
    resolve(RecordBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetPayment());
    $source = $f['line']->movements()->firstOrFail();
    $page = visit(route('events.budget.show', $f['event']));
    // Act / Assert
    $page->resize(390, 844)->click('Record refund')->type('[name="movementForm.amount"]', '50.00')
        ->select('[name="movementForm.source_id"]', (string) $source->id)->click('Save record')->assertSee('350.00 USD');
    $page->click('Edit line')->type('[name="lineForm.confirmed"]', '150.00')->type('[name="lineForm.reason"]', 'Agreed cancellation fee')->click('Save record')
        ->assertSee('All recorded lines balanced')->click('All budget history')->assertSee('Agreed cancellation fee')->assertNoJavaScriptErrors();
});

test('long financial rows fit a phone and form controls support keyboard and touch', function (): void {
    // Arrange
    $f = budgetFixture(['description' => str_repeat('Long financial description ', 8), 'counterparty' => str_repeat('LongCounterparty', 12), 'planned_cents' => 999999999, 'confirmed_cents' => 999999999]);
    $page = visit(route('events.budget.show', $f['event']));
    // Act / Assert
    $page->resize(390, 844)->assertScript('document.getElementById("main-content").scrollWidth <= document.getElementById("main-content").clientWidth')
        ->click('Edit line')->assertScript('Array.from(document.querySelectorAll("[data-budget-draft] input:not([type=checkbox]):not([type=hidden]), [data-budget-draft] select")).every(element => element.getBoundingClientRect().height >= 44)')
        ->keys('[name="lineForm.direction"]', ['Tab'])->assertScript('document.activeElement.name === "lineForm.category"')
        ->keys('[name="lineForm.category"]', ['Tab'])->assertScript('document.activeElement.name === "lineForm.description"')
        ->assertNoJavaScriptErrors();
});
