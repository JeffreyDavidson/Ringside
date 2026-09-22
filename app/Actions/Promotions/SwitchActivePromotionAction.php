<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Session\Session;

final readonly class SwitchActivePromotionAction
{
    public function __construct(private PromotionContextService $context) {}

    public function handle(User $user, int $promotionId, Session $session): void
    {
        $promotion = $user->promotions()
            ->whereKey($promotionId)
            ->wherePivot('status', MembershipStatus::Active)
            ->first();

        if (! $promotion instanceof Promotion) {
            throw new AuthorizationException('The selected promotion membership is not active.');
        }

        $session->put('active_promotion_id', $promotion->getKey());
        $this->context->set($promotion);
    }
}
