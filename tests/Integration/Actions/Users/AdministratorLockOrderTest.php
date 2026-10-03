<?php

declare(strict_types=1);

use App\Actions\Users\ChangeStatusAction;
use App\Actions\Users\UpdateAction;
use App\Data\Users\UserData;
use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Users\User;

dataset('administrator lock order changes', [
    'deactivate' => [fn (User $target): User => resolve(ChangeStatusAction::class)->handle($target, UserStatus::Inactive)],
    'demote' => [fn (User $target): User => resolve(UpdateAction::class)->handle(
        $target,
        new UserData($target->first_name, $target->last_name, $target->email, Role::Basic, null),
    )],
]);

test('it locks the active administrators in ascending id order before the target user', function (Closure $change): void {
    // Arrange
    administrator();
    $target = administrator();

    // Act
    $statements = recordStatements(fn () => $change($target));

    // Assert
    $userLocks = collect($statements)
        ->filter(fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "users"'))
        ->values();
    $administratorSetLock = $userLocks->firstOrFail();
    $targetLock = $userLocks->last() ?? throw new RuntimeException('Expected the target user to be locked.');

    expect($userLocks)->toHaveCount(2)
        ->and($administratorSetLock['sql'])->toContain('order by "id" asc')
        ->and($administratorSetLock['bindings'])->toBe([Role::Administrator->value, UserStatus::Active->value])
        ->and($targetLock['bindings'])->toContain($target->id)
        ->and($target->refresh()->role === Role::Administrator && $target->status === UserStatus::Active)->toBeFalse();
})->with('administrator lock order changes');
