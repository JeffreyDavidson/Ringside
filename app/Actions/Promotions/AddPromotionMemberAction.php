<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;

final class AddPromotionMemberAction
{
    /** Returns false when the user is already a member. */
    public function handle(Promotion $promotion, User $user, MembershipRole $role): bool
    {
        return DB::transaction(function () use ($promotion, $user, $role): bool {
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
                return false;
            }

            $lockedPromotion->users()->attach($activeUser->getKey(), [
                'role' => $role,
                'status' => MembershipStatus::Active,
            ]);

            app(PromotionContextService::class)->forgetMemberships();

            return true;
        });
    }
}
