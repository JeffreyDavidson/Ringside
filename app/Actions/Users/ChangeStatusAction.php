<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Promotions\EnsureAnotherActiveOwnerAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionMembership;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

final readonly class ChangeStatusAction
{
    public function __construct(
        private EnsureAnotherActiveAdministratorAction $ensureAnotherActiveAdministrator,
        private EnsureAnotherActiveOwnerAction $ensureAnotherActiveOwner,
    ) {}

    public function handle(User $user, UserStatus $status): User
    {
        return DB::transaction(function () use ($user, $status): User {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->status !== $status) {
                if ($status !== UserStatus::Active && $lockedUser->role->isAdministrator()) {
                    $this->ensureAnotherActiveAdministrator->handle($lockedUser);
                }

                if ($lockedUser->status === UserStatus::Active) {
                    $this->ensureNotSoleActiveOwner($lockedUser);
                }

                $lockedUser->status = $status;
                $lockedUser->save();
            }

            return $lockedUser;
        });
    }

    /**
     * An owner whose account is deactivated no longer counts as an active owner, so deactivating the last one
     * would leave a promotion without anyone who can manage it. Locks each owned promotion in ascending id order,
     * then its membership row, the same order as the promotion member Actions; the memberships are first read
     * without a lock so no membership row is locked before its promotion.
     */
    private function ensureNotSoleActiveOwner(User $lockedUser): void
    {
        $ownedPromotionIds = PromotionMembership::query()
            ->forUser($lockedUser)
            ->active()
            ->withRole(MembershipRole::Owner)
            ->orderBy('promotion_id')
            ->pluck('promotion_id');

        $ownedPromotions = Promotion::query()
            ->whereKey($ownedPromotionIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($ownedPromotions as $lockedPromotion) {
            $lockedMembership = $lockedPromotion->memberships()
                ->forUser($lockedUser)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureAnotherActiveOwner->handle($lockedPromotion, $lockedMembership);
        }
    }
}
