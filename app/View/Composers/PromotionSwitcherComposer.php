<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Enums\Promotions\MembershipStatus;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class PromotionSwitcherComposer
{
    public function __construct(private readonly PromotionContextService $context) {}

    public function compose(View $view): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $view->with('promotionSwitcherPromotions', collect())
                ->with('activePromotionId', null);

            return;
        }

        $promotions = $user->promotions()
            ->wherePivot('status', MembershipStatus::Active)
            ->orderBy('promotions.name')
            ->get();

        $activePromotionId = $this->context->current()?->getKey();

        if (! is_int($activePromotionId)) {
            $activePromotionId = filter_var(session('active_promotion_id'), FILTER_VALIDATE_INT);
        }

        if (! is_int($activePromotionId)) {
            $activePromotionId = $promotions->first()?->getKey();
        }

        $view->with('promotionSwitcherPromotions', $promotions)
            ->with('activePromotionId', $activePromotionId);
    }
}
