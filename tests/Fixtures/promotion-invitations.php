<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;

function inviteTo(Promotion $promotion, User $user, MembershipRole $role = MembershipRole::Manager): PromotionInvitation
{
    return PromotionInvitation::factory()->for($promotion)->forEmail($user->email)->withRole($role)->create();
}

function joinAs(Promotion $promotion, User $user, MembershipRole $role = MembershipRole::Member, MembershipStatus $status = MembershipStatus::Active): void
{
    $promotion->users()->attach($user, ['role' => $role, 'status' => $status]);
}
