<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

final class AddPromotionMemberAction
{
    public function handle(Promotion $promotion, User $user, MembershipRole $role): void
    {
        DB::transaction(function () use ($promotion, $user, $role): void {
            $lockedPromotion = Promotion::query()
                ->whereKey($promotion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $activeUser = User::query()
                ->whereKey($user->getKey())
                ->where('status', UserStatus::Active)
                ->lockForUpdate()
                ->firstOrFail();

            $membership = $lockedPromotion->memberships()
                ->where('user_id', $activeUser->getKey())
                ->lockForUpdate()
                ->first();

            if ($membership !== null) {
                return;
            }

            $lockedPromotion->users()->attach($activeUser->getKey(), [
                'role' => $role,
                'status' => MembershipStatus::Active,
            ]);
        });
    }
}
