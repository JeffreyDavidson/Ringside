<?php

declare(strict_types=1);

use App\Collections\EventBudgetMovementCollection;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetMovement;

/** @return array{payment: EventBudgetMovement, otherPayment: EventBudgetMovement, refund: EventBudgetMovement, voidedRefund: EventBudgetMovement, otherRefund: EventBudgetMovement, voidedPayment: EventBudgetMovement, movements: EventBudgetMovementCollection} */
function budgetMovementsOnOneLine(): array
{
    $line = EventBudgetLine::factory()->create();
    $payment = EventBudgetMovement::factory()->for($line, 'line')->create(['amount_cents' => 30000]);
    $otherPayment = EventBudgetMovement::factory()->for($line, 'line')->create(['amount_cents' => 10000]);
    $refund = EventBudgetMovement::factory()->for($line, 'line')->create(['kind' => 'refund', 'amount_cents' => 2000, 'source_id' => $payment->id]);
    $voidedRefund = EventBudgetMovement::factory()->for($line, 'line')->create(['kind' => 'refund', 'amount_cents' => 500, 'source_id' => $payment->id, 'voided_at' => now()]);
    $otherRefund = EventBudgetMovement::factory()->for($line, 'line')->create(['kind' => 'refund', 'amount_cents' => 700, 'source_id' => $otherPayment->id]);
    $voidedPayment = EventBudgetMovement::factory()->for($line, 'line')->create(['amount_cents' => 900, 'voided_at' => now()]);

    return [
        'payment' => $payment,
        'otherPayment' => $otherPayment,
        'refund' => $refund,
        'voidedRefund' => $voidedRefund,
        'otherRefund' => $otherRefund,
        'voidedPayment' => $voidedPayment,
        'movements' => $line->movements()->get(),
    ];
}

it('is the collection a line returns for its movements', function (): void {
    // Arrange
    $line = EventBudgetLine::factory()->create();
    EventBudgetMovement::factory()->for($line, 'line')->create();

    // Act
    $loaded = EventBudgetLine::query()->with('movements')->findOrFail($line->id);

    // Assert
    expect($loaded->movements)->toBeInstanceOf(EventBudgetMovementCollection::class);
});

it('keeps the owning line reachable from an eager loaded movement', function (): void {
    // Arrange
    $line = EventBudgetLine::factory()->create();
    EventBudgetMovement::factory()->for($line, 'line')->create();

    // Act
    $movement = EventBudgetMovement::query()->with('line')->firstOrFail();

    // Assert
    expect($movement->line->is($line))->toBeTrue();
});

it('drops the voided movements', function (): void {
    // Arrange
    $fixture = budgetMovementsOnOneLine();

    // Act
    $unvoided = $fixture['movements']->unvoided();

    // Assert
    expect($unvoided)->toBeInstanceOf(EventBudgetMovementCollection::class)
        ->and($unvoided->modelKeys())->toBe([
            $fixture['payment']->id,
            $fixture['otherPayment']->id,
            $fixture['refund']->id,
            $fixture['otherRefund']->id,
        ]);
});

it('finds the movements sourced from a payment, voided ones included', function (): void {
    // Arrange
    $fixture = budgetMovementsOnOneLine();

    // Act
    $refunds = $fixture['movements']->refundsOf($fixture['payment']);

    // Assert
    expect($refunds)->toBeInstanceOf(EventBudgetMovementCollection::class)
        ->and($refunds->modelKeys())->toBe([$fixture['refund']->id, $fixture['voidedRefund']->id]);
});

it('finds the movements sourced from a payment id', function (): void {
    // Arrange
    $fixture = budgetMovementsOnOneLine();

    // Act
    $refunds = $fixture['movements']->refundsOf($fixture['otherPayment']->id);

    // Assert
    expect($refunds->modelKeys())->toBe([$fixture['otherRefund']->id]);
});

it('chains refundsOf with unvoided to leave only refunds still in force', function (): void {
    // Arrange
    $fixture = budgetMovementsOnOneLine();

    // Act
    $refunds = $fixture['movements']->refundsOf($fixture['payment'])->unvoided();

    // Assert
    expect($refunds->modelKeys())->toBe([$fixture['refund']->id]);
});

it('finds nothing for a payment with no refunds', function (): void {
    // Arrange
    $fixture = budgetMovementsOnOneLine();

    // Act
    $refunds = $fixture['movements']->refundsOf($fixture['voidedPayment']);

    // Assert
    expect($refunds)->toBeEmpty();
});
