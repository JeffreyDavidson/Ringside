<?php

declare(strict_types=1);
use App\Actions\Events\RecordBudgetMovementAction;
use App\Actions\Events\SaveBudgetLineAction;
use App\Exceptions\Events\BudgetException;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetMovement;
use App\Models\Events\EventBudgetRevision;
use App\Models\Promotions\Promotion;
use App\Services\Events\BudgetSettlementService;
use App\Services\Promotions\PromotionContextService;
use App\ViewModels\EventBudgetViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

test('saving an expense creates a USD budget with retained history', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create();
    $payload = ['direction' => 'expense', 'category' => 'talent', 'description' => 'Main event fee', 'counterparty' => 'Casey', 'planned' => '500.00', 'confirmed' => '500.00', 'due_date' => '', 'notes' => '', 'reason' => '', 'acknowledge' => false];

    // Act
    $budget = resolve(SaveBudgetLineAction::class)->handle($event, 0, (string) Str::uuid(), null, $payload);

    // Assert
    expect($budget->currency)->toBe('USD')->and($budget->version)->toBe(1)
        ->and($budget->lines()->firstOrFail()->confirmed_cents)->toBe(50000)
        ->and($budget->revisions()->count())->toBe(1)
        ->and(EventBudget::query()->count())->toBe(1);
});

test('an expense deposit leaves the confirmed balance outstanding', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create();
    $budget = EventBudget::factory()->for($event)->create();
    $line = EventBudgetLine::factory()->for($budget, 'budget')->create();

    // Act
    $saved = resolve(RecordBudgetMovementAction::class)->handle($event, 0, (string) Str::uuid(), $line->id, ['kind' => 'payment', 'amount' => '200.00', 'effective_date' => today()->toDateString(), 'method' => 'cash']);

    // Assert
    expect($saved->version)->toBe(1)
        ->and(resolve(BudgetSettlementService::class)->net($line->load('movements')))->toBe(20000)
        ->and($line->refresh()->confirmed_cents)->toBe(50000);
});

test('budget totals keep income, expenses and refund balances separate', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create();
    $budget = EventBudget::factory()->for($event)->create();
    $fee = EventBudgetLine::factory()->for($budget, 'budget')->create(['planned_cents' => 50000, 'confirmed_cents' => 0]);
    EventBudgetMovement::factory()->for($fee, 'line')->create(['amount_cents' => 20000]);
    $tickets = EventBudgetLine::factory()->for($budget, 'budget')->create(['direction' => 'income', 'category' => 'tickets', 'planned_cents' => 100000, 'confirmed_cents' => 100000]);

    // Act
    $data = resolve(EventBudgetViewModel::class)->data($event);

    // Assert
    expect($data['totals']['to_collect'])->toBe(100000)
        ->and($data['totals']['refund_to_collect'])->toBe(20000)
        ->and($data['totals']['to_pay'])->toBe(0)
        ->and($data['totals']['confirmed_net'])->toBe(100000)
        ->and($data['totals']['balanced'])->toBeFalse();
});

require_once __DIR__.'/../../../Fixtures/budget.php';

test('a changed confirmed obligation requires a reason and acknowledges an excess', function (): void {
    // Arrange
    $f = budgetFixture();
    EventBudgetMovement::factory()->for($f['line'], 'line')->create();
    $action = resolve(SaveBudgetLineAction::class);
    // Act / Assert
    expect(fn () => $action->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetLineInput(['confirmed' => '0'])))->toThrow(BudgetException::class);
    expect(fn () => $action->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetLineInput(['confirmed' => '0', 'reason' => 'Cancelled'])))->toThrow(BudgetException::class);
    // Act
    $key = budgetKey();
    $input = budgetLineInput(['confirmed' => '0', 'reason' => 'Cancelled', 'acknowledge' => true]);
    $action->handle($f['event'], 0, $key, $f['line']->id, $input);
    $action->handle($f['event'], 0, $key, $f['line']->id, $input);
    // Assert
    expect($f['line']->refresh()->confirmed_cents)->toBe(0)->and($f['budget']->revisions()->count())->toBe(1);
});

test('unchanged line saves do not append history', function (): void {
    // Arrange
    $f = budgetFixture();
    $action = resolve(SaveBudgetLineAction::class);
    $input = budgetLineInput();
    // Act
    $action->handle($f['event'], 0, budgetKey(), $f['line']->id, $input);
    $version = $f['budget']->refresh()->version;
    $action->handle($f['event'], $version, budgetKey(), $f['line']->id, $input);
    // Assert
    expect($f['budget']->refresh()->version)->toBe($version);
});

test('line direction category and archived guards protect saved money', function (string $case): void {
    // Arrange
    $f = budgetFixture();
    $input = budgetLineInput();
    if ($case === 'direction') {
        EventBudgetMovement::factory()->for($f['line'], 'line')->create();
        $input['direction'] = 'income';
        $input['category'] = 'tickets';
    }
    if ($case === 'category') {
        $input['category'] = 'tickets';
    }
    if ($case === 'archived') {
        $f['line']->archived_at = now();
        $f['line']->save();
    }
    // Act / Assert
    expect(fn () => resolve(SaveBudgetLineAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, $input))->toThrow(BudgetException::class);
    expect($f['budget']->revisions()->count())->toBe(0);
})->with(['direction', 'category', 'archived']);

test('a soft-deleted event refuses financial writes and restoration preserves its budget', function (): void {
    // Arrange
    $f = budgetFixture();
    $f['event']->delete();
    // Act / Assert
    expect(fn () => resolve(SaveBudgetLineAction::class)->handle($f['event'], 0, budgetKey(), null, budgetLineInput()))->toThrow(ModelNotFoundException::class);
    $f['event']->restore();
    expect(EventBudget::query()->where('event_id', $f['event']->id)->count())->toBe(1)->and($f['line']->refresh()->confirmed_cents)->toBe(50000);
});

test('line saves reauthorize the active membership and enforce foreign context', function (string $role): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->create();
    $user = $role === 'administrator_foreign' ? administrator() : basicUser();
    $promotion->users()->attach($user, ['role' => $role === 'suspended' ? 'owner' : 'member', 'status' => $role === 'suspended' ? 'suspended' : 'active']);
    \Pest\Laravel\actingAs($user);
    $context = app(PromotionContextService::class);
    $context->set($role === 'administrator_foreign' ? Promotion::factory()->create() : $promotion);
    $context->enforce();
    // Act / Assert
    expect(fn () => resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput()))->toThrow($role === 'administrator_foreign' ? ModelNotFoundException::class : AuthorizationException::class);
    expect(EventBudget::query()->count())->toBe(0)->and(EventBudgetRevision::query()->count())->toBe(0);
})->with(['member', 'suspended', 'administrator_foreign']);

test('guests cannot invoke budget actions even with valid fields', function (): void {
    // Arrange
    $event = Event::factory()->create();
    // Act / Assert
    expect(fn () => resolve(SaveBudgetLineAction::class)->handle($event, 0, budgetKey(), null, budgetLineInput()))->toThrow(AuthorizationException::class);
    expect(EventBudget::query()->count())->toBe(0);
});

test('budget creation retries do not change currency timezone or line identity', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $event = Event::factory()->for($promotion, 'promotion')->create();
    $key = budgetKey();
    $action = resolve(SaveBudgetLineAction::class);
    // Act
    $budget = $action->handle($event, 0, $key, null, budgetLineInput());
    $promotion->timezone = 'America/Los_Angeles';
    $promotion->save();
    $action->handle($event, 0, $key, null, budgetLineInput());
    // Assert
    expect($budget->refresh()->timezone)->toBe('America/New_York')->and($budget->currency)->toBe('USD')->and($budget->lines()->count())->toBe(1)->and($budget->version)->toBe(1);
});

test('permanent event deletion is restricted by the retained budget', function (): void {
    // Arrange
    $f = budgetFixture();
    // Act / Assert
    expect(fn () => DB::transaction(fn () => $f['event']->forceDelete()))->toThrow(QueryException::class);
    expect(Event::query()->whereKey($f['event']->id)->exists())->toBeTrue()->and($f['budget']->revisions()->count())->toBe(0);
});

test('line limits and input validation cannot leave an empty budget or partial history', function (string $case): void {
    // Arrange
    $f = budgetFixture();
    $input = budgetLineInput();
    $key = budgetKey();
    if ($case === 'limit') {
        EventBudgetLine::factory()->count(499)->for($f['budget'], 'budget')->create();
    }
    if ($case === 'input') {
        $input['planned'] = '1.001';
    }
    if ($case === 'key') {
        $key = 'not-a-uuid';
    }
    // Act / Assert
    expect(fn () => resolve(SaveBudgetLineAction::class)->handle($f['event'], 0, $key, null, $input))->toThrow($case === 'limit' ? BudgetException::class : ValidationException::class);
    expect($f['budget']->revisions()->count())->toBe(0)->and($f['budget']->refresh()->version)->toBe(0);
})->with(['limit', 'input', 'key']);
