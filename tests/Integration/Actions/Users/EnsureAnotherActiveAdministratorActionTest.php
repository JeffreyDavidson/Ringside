<?php

declare(strict_types=1);

use App\Actions\Users\ChangeStatusAction;
use App\Actions\Users\UpdateAction;
use App\Data\Users\UserData;
use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Exceptions\Users\CannotRemoveLastAdministratorException;
use App\Models\Users\User;

dataset('administrator-removing changes', [
    'deactivate' => [fn (User $u) => resolve(ChangeStatusAction::class)->handle($u, UserStatus::Inactive)],
    'mark unverified' => [fn (User $u) => resolve(ChangeStatusAction::class)->handle($u, UserStatus::Unverified)],
    'demote' => [fn (User $u) => resolve(UpdateAction::class)->handle($u, new UserData('A', 'B', $u->email, Role::Basic, null))],
]);

test('the sole active administrator cannot be deactivated or demoted', function (Closure $change): void {
    $administrator = administrator();

    expect(fn () => $change($administrator))->toThrow(CannotRemoveLastAdministratorException::class);

    $administrator->refresh();
    expect($administrator->role)->toBe(Role::Administrator)
        ->and($administrator->status)->toBe(UserStatus::Active);
})->with('administrator-removing changes');

test('an inactive second administrator does not count', function (Closure $change): void {
    $administrator = administrator();
    User::factory()->administrator()->create(['status' => UserStatus::Inactive]);

    expect(fn () => $change($administrator))->toThrow(CannotRemoveLastAdministratorException::class);
})->with('administrator-removing changes');

test('an administrator can be deactivated or demoted while another active administrator remains', function (Closure $change): void {
    $administrator = administrator();
    administrator();

    $change($administrator);

    $administrator->refresh();
    expect($administrator->role === Role::Administrator && $administrator->status === UserStatus::Active)->toBeFalse();
})->with('administrator-removing changes');

test('inactive administrators and basic users are never blocked', function (): void {
    administrator();
    $inactiveAdministrator = User::factory()->administrator()->create(['status' => UserStatus::Inactive]);
    $basic = basicUser();

    resolve(ChangeStatusAction::class)->handle($basic, UserStatus::Inactive);
    resolve(UpdateAction::class)->handle(
        $inactiveAdministrator,
        new UserData('A', 'B', $inactiveAdministrator->email, Role::Basic, null),
    );

    expect($basic->refresh()->status)->toBe(UserStatus::Inactive)
        ->and($inactiveAdministrator->refresh()->role)->toBe(Role::Basic);
});

test('keeping the administrator role or activating never trips the guard', function (): void {
    $administrator = administrator();

    resolve(ChangeStatusAction::class)->handle($administrator, UserStatus::Active);
    resolve(UpdateAction::class)->handle(
        $administrator,
        new UserData('Still', 'Admin', $administrator->email, Role::Administrator, null),
    );

    expect($administrator->refresh()->last_name)->toBe('Admin');
});
