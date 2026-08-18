<?php

declare(strict_types=1);

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
