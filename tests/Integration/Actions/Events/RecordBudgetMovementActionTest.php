<?php

declare(strict_types=1);
use App\Actions\Events\RecordBudgetMovementAction;
use App\Data\Events\BudgetMovementData;
use App\Enums\Events\BudgetMovementKind;
use App\Exceptions\Events\BudgetException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetMovement;
use App\Services\Events\BudgetSettlementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

require_once __DIR__.'/../../../Fixtures/budget.php';

test('payment retries are harmless and a changed payload cannot reuse the same key', function (): void {
    // Arrange
    $f = budgetFixture();
    $key = budgetKey();
    $action = resolve(RecordBudgetMovementAction::class);
    // Act
    $action->handle($f['event'], 0, $key, $f['line']->id, budgetMovementData());
    $retry = $action->handle($f['event'], 0, $key, $f['line']->id, budgetMovementData());
    // Assert
    expect($retry->version)->toBe(1)->and($f['line']->movements()->count())->toBe(1)->and($f['budget']->revisions()->count())->toBe(1);
    expect(fn () => $action->handle($f['event'], 0, $key, $f['line']->id, budgetMovementData(['amount' => '100.00'])))->toThrow(BudgetException::class)
        ->and($f['line']->movements()->count())->toBe(1);
});

test('new payment identities require the latest version and never overwrite cash', function (): void {
    // Arrange
    $f = budgetFixture();
    $action = resolve(RecordBudgetMovementAction::class);
    // Act
    $action->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetMovementData());
    // Assert
    expect(fn () => $action->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetMovementData()))->toThrow(RecordChangedException::class);
    // Act
    $action->handle($f['event'], 1, budgetKey(), $f['line']->id, budgetMovementData(['amount' => '300.00']));
    // Assert
    expect(resolve(BudgetSettlementService::class)->net($f['line']->load('movements')))->toBe(50000)->and($f['line']->movements()->count())->toBe(2);
});

test('a refund changes cash but leaves the confirmed obligation alone', function (): void {
    // Arrange
    $f = budgetFixture(['confirmed_cents' => 5000]);
    $action = resolve(RecordBudgetMovementAction::class);
    $action->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetMovementData(['acknowledge' => true]));
    $source = $f['line']->movements()->firstOrFail();
    // Act
    $action->handle($f['event'], 1, budgetKey(), $f['line']->id, budgetMovementData(['kind' => 'refund', 'amount' => '150.00', 'source_id' => $source->id]));
    // Assert
    $line = $f['line']->refresh()->load('movements');
    expect(resolve(BudgetSettlementService::class)->row($line)['balance'])->toBe(0)->and($line->confirmed_cents)->toBe(5000)->and($line->movements)->toHaveCount(2);
});

test('refunds enforce same-line ownership original kind date and remaining cap', function (string $case): void {
    // Arrange
    $f = budgetFixture();
    $source = EventBudgetMovement::factory()->for($f['line'], 'line')->create(['amount_cents' => 20000]);
    $input = budgetPayment(['kind' => 'refund', 'amount' => '100.00', 'source_id' => $source->id]);
    if ($case === 'foreign') {
        $input['source_id'] = EventBudgetMovement::factory()->create()->id;
    }
    if ($case === 'voided') {
        $source->voided_at = now();
        $source->save();
    }
    if ($case === 'refund') {
        $source->kind = BudgetMovementKind::Refund;
        $source->save();
    }
    if ($case === 'early') {
        $input['effective_date'] = today()->subDay()->toDateString();
    }
    if ($case === 'missing') {
        $input['source_id'] = null;
    }
    if ($case === 'cap') {
        EventBudgetMovement::factory()->for($f['line'], 'line')->create(['kind' => 'refund', 'source_id' => $source->id, 'amount_cents' => 15000]);
    }
    // Act / Assert
    $count = EventBudgetMovement::query()->count();
    expect(fn () => resolve(RecordBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, BudgetMovementData::fromValidated($input)))->toThrow(BudgetException::class)
        ->and(EventBudgetMovement::query()->count())->toBe($count)
        ->and($f['budget']->revisions()->count())->toBe(0);
})->with(['foreign', 'voided', 'refund', 'early', 'missing', 'cap']);

test('only live refunds of the same payment count against its remaining cap', function (): void {
    // Arrange
    $f = budgetFixture();
    $source = EventBudgetMovement::factory()->for($f['line'], 'line')->create(['amount_cents' => 20000]);
    $other = EventBudgetMovement::factory()->for($f['line'], 'line')->create(['amount_cents' => 20000]);
    EventBudgetMovement::factory()->for($f['line'], 'line')->create(['kind' => 'refund', 'source_id' => $source->id, 'amount_cents' => 15000, 'voided_at' => now()]);
    EventBudgetMovement::factory()->for($f['line'], 'line')->create(['kind' => 'refund', 'source_id' => $other->id, 'amount_cents' => 15000]);
    $input = budgetPayment(['kind' => 'refund', 'amount' => '100.00', 'source_id' => $source->id]);

    // Act
    resolve(RecordBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, BudgetMovementData::fromValidated($input));

    // Assert
    expect($f['line']->movements()->where('source_id', $source->id)->whereNull('voided_at')->pluck('amount_cents')->all())->toBe([10000]);
});

test('payment amount date and source misuse cannot create a movement', function (array $override): void {
    // Arrange
    $f = budgetFixture();
    // Act / Assert
    expect(fn () => resolve(RecordBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetMovementData($override)))->toThrow(BudgetException::class);
    expect($f['line']->movements()->count())->toBe(0)->and($f['budget']->revisions()->count())->toBe(0);
})->with([
    'zero' => [['amount' => '0']], 'future' => [['effective_date' => '2099-01-01']],
    'payment source' => [['source_id' => 1]], 'excess' => [['amount' => '501.00']],
]);

test('unknown obligations admit deposits without inventing a balance', function (): void {
    // Arrange
    $f = budgetFixture(['confirmed_cents' => null, 'planned_cents' => null]);
    // Act
    resolve(RecordBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetMovementData());
    // Assert
    $row = resolve(BudgetSettlementService::class)->row($f['line']->load('movements'));
    expect($row['balance'])->toBeNull()->and($row['variance'])->toBeNull()->and($row['attention'])->toBe('needs_amount');
});

test('an archived line refuses payments and a guessed foreign line changes nothing', function (bool $archived): void {
    // Arrange
    $f = budgetFixture();
    $line = $archived ? $f['line'] : EventBudgetLine::factory()->create();
    if ($archived) {
        $line->archived_at = now();
        $line->save();
    }
    // Act / Assert
    expect(fn () => resolve(RecordBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $line->id, budgetMovementData()))->toThrow($archived ? BudgetException::class : ModelNotFoundException::class);
    expect(EventBudgetMovement::query()->count())->toBe(0);
})->with([true, false]);

test('the retained movement limit refuses another payment without changing history', function (): void {
    // Arrange
    $f = budgetFixture(['confirmed_cents' => null]);
    EventBudgetMovement::factory()->count(1000)->for($f['line'], 'line')->create();
    // Act / Assert
    expect(fn () => resolve(RecordBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetMovementData()))->toThrow(BudgetException::class);
    expect($f['line']->movements()->count())->toBe(1000)->and($f['budget']->revisions()->count())->toBe(0);
});

test('UUID case is normalized consistently across database engines', function (): void {
    // Arrange
    $f = budgetFixture();
    $key = 'a1111111-1111-4111-8111-111111111111';
    $action = resolve(RecordBudgetMovementAction::class);
    // Act
    $action->handle($f['event'], 0, $key, $f['line']->id, budgetMovementData());
    $action->handle($f['event'], 1, mb_strtoupper($key), $f['line']->id, budgetMovementData());
    // Assert
    expect($f['line']->movements()->count())->toBe(1)->and($f['budget']->revisions()->count())->toBe(1);
});
