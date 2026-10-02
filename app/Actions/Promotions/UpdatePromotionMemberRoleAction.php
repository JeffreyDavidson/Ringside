<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePromotionMemberRoleAction
{
    public function __construct(private EnsureAnotherActiveOwnerAction $ensureAnotherActiveOwner) {}

    public function handle(Promotion $promotion, User $user, MembershipRole $role): void
    {
        DB::transaction(function () use ($promotion, $user, $role): void {
            $lockedPromotion = Promotion::query()
                ->whereKey($promotion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $membership = $lockedPromotion->memberships()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($role !== MembershipRole::Owner) {
                $this->ensureAnotherActiveOwner->handle($lockedPromotion, $membership);
            }

            $lockedPromotion->users()->updateExistingPivot($user->getKey(), [
                'role' => $role,
            ]);

            app(PromotionContextService::class)->forgetMemberships();
        });
    }
}
