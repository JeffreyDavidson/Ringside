<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;

final class RemovePromotionInvitationAction
{
    /**
     * Delete the user's pending invitation: the owner cancelling it or the invited user declining it. Only an
     * `Invited` membership is ever removed, so an active or suspended member is never affected. Returns false
     * when there is no pending invitation.
     */
    public function handle(Promotion $promotion, User $user): bool
    {
        return DB::transaction(function () use ($promotion, $user): bool {
            $lockedPromotion = Promotion::query()
                ->whereKey($promotion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $removed = $lockedPromotion->memberships()
                ->forUser($user)
                ->invited()
                ->delete();

            app(PromotionContextService::class)->forgetMemberships();

            return $removed > 0;
        });
    }
}
