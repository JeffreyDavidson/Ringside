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
     * Turn the invitation for the user's own email into an active membership with the role the owner chose
     * and delete the invitation. The invitation is found through the user's email, never through an id from
     * the request, so nobody can accept for another email. Returns the role the user now holds, or null when
     * there is no such invitation (cancelled, declined or already accepted) or the user already has a
     * membership of the promotion: an invitation can never undo a suspension or change an existing role.
     */
    public function handle(Promotion $promotion, User $user): ?MembershipRole
    {
        return DB::transaction(function () use ($promotion, $user): ?MembershipRole {
            $lockedPromotion = Promotion::query()
                ->whereKey($promotion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $invitation = $lockedPromotion->invitations()
                ->forEmail($user->email)
                ->lockForUpdate()
                ->first();

            if ($invitation === null) {
                return null;
            }

            if ($lockedPromotion->memberships()->forUser($user)->exists()) {
                return null;
            }

            $lockedPromotion->users()->attach($user->getKey(), [
                'role' => $invitation->role,
                'status' => MembershipStatus::Active,
            ]);

            $invitation->delete();

            app(PromotionContextService::class)->forgetMemberships();

            return $invitation->role;
        });
    }
}
