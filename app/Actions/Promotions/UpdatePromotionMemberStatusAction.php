<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePromotionMemberStatusAction
{
    public function __construct(private EnsureAnotherActiveOwnerAction $ensureAnotherActiveOwner) {}

    public function handle(Promotion $promotion, User $user, MembershipStatus $status): void
    {
        DB::transaction(function () use ($promotion, $user, $status): void {
            $lockedPromotion = Promotion::query()
                ->whereKey($promotion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $membership = $lockedPromotion->memberships()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($status !== MembershipStatus::Active) {
                $this->ensureAnotherActiveOwner->handle($lockedPromotion, $membership);
            }

            $lockedPromotion->users()->updateExistingPivot($user->getKey(), [
                'status' => $status,
            ]);
        });
    }
}
