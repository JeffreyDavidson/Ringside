<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;

final class RemovePromotionInvitationAction
{
    /**
     * Delete the pending invitation for an email: the owner cancelling it or the invited user declining it.
     * Memberships are never touched. Returns false when there is no pending invitation.
     */
    public function handle(Promotion $promotion, string $email): bool
    {
        return DB::transaction(function () use ($promotion, $email): bool {
            $lockedPromotion = Promotion::query()
                ->whereKey($promotion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $removed = $lockedPromotion->invitations()
                ->forEmail($email)
                ->delete();

            app(PromotionContextService::class)->forgetMemberships();

            return $removed > 0;
        });
    }
}
