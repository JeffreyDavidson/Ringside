<?php

declare(strict_types=1);

use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;

test('it defines backed lifecycle action values', function (RosterLifecycleAction $action, string $value): void {
    expect($action->value)->toBe($value);
})->with([
    [RosterLifecycleAction::Employ, 'employ'],
    [RosterLifecycleAction::ClearFromInjury, 'clear_from_injury'],
    [RosterLifecycleAction::Injure, 'injure'],
    [RosterLifecycleAction::Reinstate, 'reinstate'],
    [RosterLifecycleAction::Release, 'release'],
    [RosterLifecycleAction::Restore, 'restore'],
    [RosterLifecycleAction::Retire, 'retire'],
    [RosterLifecycleAction::Suspend, 'suspend'],
    [RosterLifecycleAction::Unretire, 'unretire'],
]);

test('it maps lifecycle actions to policy abilities and success messages', function (
    RosterLifecycleAction $action,
    string $ability,
    string $successAction,
): void {
    expect($action->ability())->toBe($ability)
        ->and($action->successAction())->toBe($successAction);
})->with([
    [RosterLifecycleAction::Employ, 'employ', 'employed'],
    [RosterLifecycleAction::ClearFromInjury, 'clearFromInjury', 'cleared_from_injury'],
    [RosterLifecycleAction::Injure, 'injure', 'injured'],
    [RosterLifecycleAction::Reinstate, 'reinstate', 'reinstated'],
    [RosterLifecycleAction::Release, 'release', 'released'],
    [RosterLifecycleAction::Restore, 'restore', 'restored'],
    [RosterLifecycleAction::Retire, 'retire', 'retired'],
    [RosterLifecycleAction::Suspend, 'suspend', 'suspended'],
    [RosterLifecycleAction::Unretire, 'unretire', 'unretired'],
]);

test('it identifies actions that operate on trashed roster models', function (): void {
    expect(RosterLifecycleAction::Restore->usesTrashedModel())->toBeTrue()
        ->and(RosterLifecycleAction::Retire->usesTrashedModel())->toBeFalse();
});

test('it exposes the roster entities supported by each lifecycle action', function (
    RosterLifecycleAction $action,
    array $supportedEntityTypes,
): void {
    expect($action->supportedEntityTypes())->toEqual($supportedEntityTypes);
})->with([
    [RosterLifecycleAction::Employ, RosterEntityType::cases()],
    [RosterLifecycleAction::ClearFromInjury, [
        RosterEntityType::Wrestler,
        RosterEntityType::Manager,
        RosterEntityType::Referee,
    ]],
    [RosterLifecycleAction::Injure, [
        RosterEntityType::Wrestler,
        RosterEntityType::Manager,
        RosterEntityType::Referee,
    ]],
    [RosterLifecycleAction::Restore, RosterEntityType::cases()],
]);

test('it identifies whether a lifecycle action supports a roster entity', function (): void {
    expect(RosterLifecycleAction::Injure->supports(RosterEntityType::Wrestler))->toBeTrue()
        ->and(RosterLifecycleAction::Injure->supports(RosterEntityType::TagTeam))->toBeFalse()
        ->and(RosterLifecycleAction::Employ->supports(RosterEntityType::TagTeam))->toBeTrue();
});
