<?php

declare(strict_types=1);

use App\Enums\Users\Role;

test('identifies whether a role is an administrator', function (Role $role, bool $expected) {
    expect($role->isAdministrator())->toBe($expected);
})->with([
    'administrator' => [Role::Administrator, true],
    'basic' => [Role::Basic, false],
]);
