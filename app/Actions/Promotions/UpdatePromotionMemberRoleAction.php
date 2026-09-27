<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

final class UpdatePromotionMemberRoleAction
{
    public function handle(Promotion $promotion, User $user, MembershipRole $role): void
    {
        DB::transaction(function () use ($promotion, $user, $role): void {
            $lockedPromotion = Promotion::query()
                ->whereKey($promotion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPromotion->memberships()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPromotion->users()->updateExistingPivot($user->getKey(), [
                'role' => $role,
            ]);
        });
    }
}
