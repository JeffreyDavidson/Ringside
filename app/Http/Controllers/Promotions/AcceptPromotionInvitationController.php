<?php

declare(strict_types=1);

namespace App\Http\Controllers\Promotions;

use App\Actions\Promotions\AcceptPromotionInvitationAction;
use App\Enums\Promotions\MembershipRole;
use App\Http\Requests\Promotions\PromotionInvitationRequest;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Http\RedirectResponse;

class AcceptPromotionInvitationController
{
    public function __invoke(
        PromotionInvitationRequest $request,
        Promotion $promotion,
        AcceptPromotionInvitationAction $acceptInvitation,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $role = $acceptInvitation->handle($promotion, $user);

        if (! $role instanceof MembershipRole) {
            return redirect()->route('dashboard')->with('error', __('promotions.invitation_unavailable'));
        }

        return redirect()->route('dashboard')->with('status', __('promotions.invitation_accepted', [
            'promotion' => $promotion->name,
            'role' => $role->label(),
        ]));
    }
}
