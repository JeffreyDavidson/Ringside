<?php

declare(strict_types=1);

use App\Enums\Tournaments\TournamentOperation;
use App\Enums\Tournaments\TournamentOperationTarget;

it('says what each operation acts on', function (TournamentOperation $operation, TournamentOperationTarget $target): void {
    // Act
    $result = $operation->target();

    // Assert
    expect($result)->toBe($target);
})->with([
    'book' => [TournamentOperation::Book, TournamentOperationTarget::Node],
    'plan' => [TournamentOperation::Plan, TournamentOperationTarget::Node],
    'reset' => [TournamentOperation::Reset, TournamentOperationTarget::Node],
    'unbook' => [TournamentOperation::Unbook, TournamentOperationTarget::Node],
    'walkover' => [TournamentOperation::Walkover, TournamentOperationTarget::Node],
    'withdraw' => [TournamentOperation::Withdraw, TournamentOperationTarget::Entry],
    'replace' => [TournamentOperation::Replace, TournamentOperationTarget::Entry],
    'cancel' => [TournamentOperation::Cancel, TournamentOperationTarget::Tournament],
]);

it('asks for a written reason and a confirmation on every operation except booking and planning', function (TournamentOperation $operation, bool $needsReason): void {
    // Act
    $result = $operation->needsReason();

    // Assert
    expect($result)->toBe($needsReason);
})->with([
    'book' => [TournamentOperation::Book, false],
    'plan' => [TournamentOperation::Plan, false],
    'reset' => [TournamentOperation::Reset, true],
    'unbook' => [TournamentOperation::Unbook, true],
    'withdraw' => [TournamentOperation::Withdraw, true],
    'replace' => [TournamentOperation::Replace, true],
    'walkover' => [TournamentOperation::Walkover, true],
    'cancel' => [TournamentOperation::Cancel, true],
]);
