<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;

test('labels each membership role for display', function (MembershipRole $role, string $label) {
    expect($role->label())->toBe($label);
})->with([
    'owner' => [MembershipRole::Owner, 'Owner'],
    'manager' => [MembershipRole::Manager, 'Manager'],
    'member' => [MembershipRole::Member, 'Member'],
]);
