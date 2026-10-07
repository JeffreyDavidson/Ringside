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

test('grants the stable restructuring abilities to owners and managers only', function (MembershipRole $role, string $ability, bool $allowed) {
    expect($role->allows($ability))->toBe($allowed);
})->with([
    'owner merges' => [MembershipRole::Owner, 'merge', true],
    'owner splits' => [MembershipRole::Owner, 'split', true],
    'owner reunites' => [MembershipRole::Owner, 'reunite', true],
    'manager merges' => [MembershipRole::Manager, 'merge', true],
    'manager splits' => [MembershipRole::Manager, 'split', true],
    'manager reunites' => [MembershipRole::Manager, 'reunite', true],
    'member merges' => [MembershipRole::Member, 'merge', false],
    'member splits' => [MembershipRole::Member, 'split', false],
    'member reunites' => [MembershipRole::Member, 'reunite', false],
]);
