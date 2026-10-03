<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipStatus;

test('labels each membership status for display', function (MembershipStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    'invited' => [MembershipStatus::Invited, 'Invited'],
    'active' => [MembershipStatus::Active, 'Active'],
    'suspended' => [MembershipStatus::Suspended, 'Suspended'],
]);
