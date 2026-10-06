<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\PromotionInvitationOutcome;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class InvitePromotionMemberAction
{
    /**
     * Save a pending invitation for the email with the role the owner chose. It grants nothing until whoever
     * signs in with that email accepts it (AcceptPromotionInvitationAction). Whether the email belongs to an
     * account never matters: unknown, inactive and active accounts all get an invitation. The only refusals
     * are an invitation that is already pending (an expired one is replaced) and an email that already has a membership of this promotion
     * (active or suspended, including a soft-deleted account that still has its membership row), both things the owner can already see.
     */
    public function handle(Promotion $promotion, string $email, MembershipRole $role): PromotionInvitationOutcome
    {
        return DB::transaction(function () use ($promotion, $email, $role): PromotionInvitationOutcome {
            $lockedPromotion = Promotion::query()
                ->whereKey($promotion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPromotion->invitations()->forEmail($email)->pending()->exists()) {
                return PromotionInvitationOutcome::AlreadyInvited;
            }

            if ($this->hasMembership($lockedPromotion, $email)) {
                return PromotionInvitationOutcome::AlreadyMember;
            }

            // An expired invitation for the email is replaced, so the unique (promotion, email) key never blocks a new one.
            $lockedPromotion->invitations()->forEmail($email)->delete();

            $lockedPromotion->invitations()->create([
                'email' => $email,
                'role' => $role,
                'expires_at' => now()->addDays(PromotionInvitation::EXPIRES_AFTER_DAYS),
            ]);

            app(PromotionContextService::class)->forgetMemberships();

            return PromotionInvitationOutcome::Invited;
        });
    }

    private function hasMembership(Promotion $promotion, string $email): bool
    {
        $normalized = PromotionInvitation::normalizeEmail($email);

        // whereLike only narrows the candidates (case-insensitively on every engine); the exact comparison is done in PHP so
        // `%` and `_` in the input can never widen the match.
        $userIds = User::query()
            ->withTrashed()
            ->whereLike('email', $normalized, caseSensitive: false)
            ->get(['id', 'email'])
            ->filter(fn (User $candidate): bool => Str::lower($candidate->email) === $normalized)
            ->modelKeys();

        return $promotion->memberships()
            ->whereIn('user_id', $userIds)
            ->exists();
    }
}
