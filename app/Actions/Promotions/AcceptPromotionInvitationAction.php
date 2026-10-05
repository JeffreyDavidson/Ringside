<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;

final class AcceptPromotionInvitationAction
{
    /**
     * Turn the user's own pending invitation into an active membership with the role the owner chose. The
     * membership is found through the invited user, never through an id from the request, so nobody can accept
     * for someone else. Returns the role the user now holds, or null when the invitation no longer exists (cancelled, declined
     * or already accepted).
     */
    public function handle(Promotion $promotion, User $user): ?MembershipRole
    {
        return DB::transaction(function () use ($promotion, $user): ?MembershipRole {
            $lockedPromotion = Promotion::query()
                ->whereKey($promotion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $membership = $lockedPromotion->memberships()
                ->forUser($user)
                ->invited()
                ->lockForUpdate()
                ->first();

            if ($membership === null) {
                return null;
            }

            $lockedPromotion->users()->updateExistingPivot($user->getKey(), [
                'status' => MembershipStatus::Active,
            ]);

            app(PromotionContextService::class)->forgetMemberships();

            return $membership->role;
        });
    }
}
