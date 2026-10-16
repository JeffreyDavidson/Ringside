<?php

declare(strict_types=1);
use App\Livewire\Events\Components\Budget;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetMovement;
use App\Models\Promotions\Promotion;
use App\Services\Events\BudgetSettlementService;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('an operator adds a fee then records a deposit through the budget form', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create();
    $component = livewire(Budget::class, ['eventId' => $event->id]);

    // Act
    $component->call('beginLine', null, 'expense')
        ->set('lineForm.description', 'Main event fee')
        ->set('lineForm.counterparty', 'Casey')
        ->set('lineForm.planned', '500.00')
        ->set('lineForm.confirmed', '500.00')
        ->call('save')->assertHasNoErrors();

    // Assert
    expect(EventBudget::query()->firstOrFail()->lines()->firstOrFail()->confirmed_cents)->toBe(50000);
});

require_once __DIR__.'/../../../../Fixtures/budget.php';

test('operators edit record refund correct archive and inspect retained history through forms', function (): void {
    // Arrange
    $f = budgetFixture();
    $component = livewire(Budget::class, ['eventId' => $f['event']->id]);
    // Act
    $component->call('beginLine', $f['line']->id)->set('lineForm.notes', 'Private editor note')->set('lineForm.due_date', today()->toDateString())->call('save')->assertHasNoErrors();
    $component->call('beginMovement', $f['line']->id)->set('movementForm.amount', '200.00')->call('save')->assertHasNoErrors();
    $source = $f['line']->movements()->firstOrFail();
    $component->call('beginMovement', $f['line']->id, 'refund')->set('movementForm.amount', '50.00')->set('movementForm.source_id', $source->id)->call('save')->assertHasNoErrors();
    $refund = $f['line']->movements()->where('kind', 'refund')->firstOrFail();
    $component->call('beginMovement', $f['line']->id, 'refund', $refund->id)->set('reason', 'Entered refund twice')->call('save')->assertHasNoErrors();
    $component->call('beginMovement', $f['line']->id, 'payment', $source->id)->set('replaceMovement', true)->set('movementForm.amount', '175.00')->set('reason', 'Wrong amount')->call('save')->assertHasNoErrors();
    $component->call('showHistory', $f['line']->id)->assertSee('Wrong amount')->assertSee('175.00 USD')->call('hideHistory');
    $component->call('showHistory')->assertSee('Private editor note')->call('hideHistory');
    $plan = EventBudgetLine::factory()->for($f['budget'], 'budget')->create(['confirmed_cents' => null]);
    $component->call('beginArchive', $plan->id)->set('reason', 'Not needed')->call('save')->assertHasNoErrors();
    // Assert
    expect($plan->refresh()->archived_at)->not->toBeNull()->and(resolve(BudgetSettlementService::class)->net($f['line']->refresh()->load('movements')))->toBe(17500);
});

test('stale forms keep their draft and require a deliberate refresh before retry', function (): void {
    // Arrange
    $f = budgetFixture();
    $first = livewire(Budget::class, ['eventId' => $f['event']->id]);
    $second = livewire(Budget::class, ['eventId' => $f['event']->id]);
    $first->call('beginLine', $f['line']->id);
    $second->call('beginLine', $f['line']->id);
    // Act
    $first->set('lineForm.description', 'Saved by first')->call('save')->assertHasNoErrors();
    $second->set('lineForm.description', 'Retained draft')->call('save')->assertHasErrors(['budget' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.'])->assertSet('lineForm.description', 'Retained draft');
    // Assert
    expect($f['line']->refresh()->description)->toBe('Saved by first');
    // Act
    $second->call('refreshState')->call('save')->assertHasNoErrors();
    // Assert
    expect($f['line']->refresh()->description)->toBe('Retained draft');
});

test('draft cancellation empty saves and malformed filters do not mutate finances', function (): void {
    // Arrange
    $f = budgetFixture();
    $component = livewire(Budget::class, ['eventId' => $f['event']->id]);
    // Act / Assert
    $component->call('save')->assertHasErrors('budget');
    $component->call('beginLine')->set('lineForm.description', 'Discarded draft')->call('cancelDraft')->assertSet('operation', null);
    $component->set('filter', 'unexpected-filter')->assertSee('Talent fee');
    $component->call('beginLine', null, 'income')->assertSet('lineForm.category', 'tickets')->call('cancelDraft');
    expect($f['budget']->revisions()->count())->toBe(0);
});

test('members cannot mount or mutate a financial component', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => 'member', 'status' => 'active']);
    actingAs($user);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    // Act / Assert
    livewire(Budget::class, ['eventId' => $event->id])->assertForbidden();
    expect(EventBudget::query()->count())->toBe(0);
});

test('membership demotion after opening a form denies the next write', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => 'manager', 'status' => 'active']);
    actingAs($user);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $component = livewire(Budget::class, ['eventId' => $event->id]);
    $component->call('beginLine')->set('lineForm.description', 'Hidden')->set('lineForm.counterparty', 'Private');
    $promotion->users()->updateExistingPivot($user->id, ['role' => 'member']);
    $context->forgetMemberships();
    // Act / Assert
    $component->call('save')->assertForbidden();
    expect(EventBudget::query()->count())->toBe(0);
});

test('membership demotion after the page opened denies the next render', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => 'manager', 'status' => 'active']);
    actingAs($user);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $component = livewire(Budget::class, ['eventId' => $event->id]);
    $promotion->users()->updateExistingPivot($user->id, ['role' => 'member']);
    $context->forgetMemberships();

    // Act
    $component->set('filter', 'to_pay');

    // Assert
    $component->assertForbidden();
});

test('invalid financial forms identify the bound field and retain the draft', function (): void {
    // Arrange
    $f = budgetFixture();
    $component = livewire(Budget::class, ['eventId' => $f['event']->id]);
    // Act / Assert
    $component->call('beginLine')->set('lineForm.description', '')->call('save')->assertHasErrors('lineForm.description');
    $component->call('beginMovement', $f['line']->id)->set('movementForm.amount', '1.001')->call('save')->assertHasErrors('movementForm.amount')->assertSet('movementForm.amount', '1.001');
    expect($f['budget']->revisions()->count())->toBe(0);
});

test('archive form validation binds a missing reason to the reason field', function (): void {
    // Arrange
    $f = budgetFixture(['confirmed_cents' => null]);
    $component = livewire(Budget::class, ['eventId' => $f['event']->id]);
    // Act / Assert
    $component->call('beginArchive', $f['line']->id)->call('save')->assertHasErrors('reason');
    expect($f['line']->refresh()->archived_at)->toBeNull();
});

test('switching from a refund to a payment clears the hidden original-payment selection', function (): void {
    // Arrange
    $f = budgetFixture();
    $source = EventBudgetMovement::factory()->for($f['line'], 'line')->create();
    $component = livewire(Budget::class, ['eventId' => $f['event']->id]);
    // Act / Assert
    $component->call('beginMovement', $f['line']->id, 'refund')->set('movementForm.source_id', $source->id)->set('movementForm.kind', 'payment')->assertSet('movementForm.source_id', null)
        ->set('movementForm.kind', 'refund')->assertSet('movementForm.source_id', null);
});
