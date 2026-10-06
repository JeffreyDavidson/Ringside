<?php

declare(strict_types=1);

namespace App\Http\Controllers\Promotions;

use App\Actions\Promotions\RemovePromotionInvitationAction;
use App\Http\Requests\Promotions\PromotionInvitationRequest;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Http\RedirectResponse;

class DeclinePromotionInvitationController
{
    public function __invoke(
        PromotionInvitationRequest $request,
        Promotion $promotion,
        RemovePromotionInvitationAction $removeInvitation,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        if (! $removeInvitation->handle($promotion, $user->email)) {
            return redirect()->route('dashboard')->with('error', __('promotions.invitation_unavailable'));
        }

        return redirect()->route('dashboard')->with('status', __('promotions.invitation_declined'));
    }
}
