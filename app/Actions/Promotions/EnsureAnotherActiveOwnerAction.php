<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Exceptions\Promotions\CannotRemoveLastOwnerException;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionMembership;

final class EnsureAnotherActiveOwnerAction
{
    /**
     * Call inside the caller's transaction after locking the promotion. Rejects when the given
     * membership is the promotion's only active owner. Another owner counts only when both the
     * membership and that owner's user account are active.
     */
    public function handle(Promotion $lockedPromotion, PromotionMembership $lockedMembership): void
    {
        if ($lockedMembership->role !== MembershipRole::Owner || $lockedMembership->status !== MembershipStatus::Active) {
            return;
        }

        $hasAnotherActiveOwner = $lockedPromotion->memberships()
            ->withRole(MembershipRole::Owner)
            ->active()
            ->withActiveUser()
            ->where('user_id', '!=', $lockedMembership->user_id)
            ->exists();

        if (! $hasAnotherActiveOwner) {
            throw CannotRemoveLastOwnerException::lastActiveOwner($lockedPromotion);
        }
    }
}
