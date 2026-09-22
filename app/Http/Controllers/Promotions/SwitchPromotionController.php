<?php

declare(strict_types=1);

namespace App\Http\Controllers\Promotions;

use App\Actions\Promotions\SwitchActivePromotionAction;
use App\Http\Requests\Promotions\SwitchPromotionRequest;
use App\Models\Users\User;
use Illuminate\Http\RedirectResponse;

class SwitchPromotionController
{
    public function __invoke(
        SwitchPromotionRequest $request,
        SwitchActivePromotionAction $switchActivePromotion,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $switchActivePromotion->handle(
            $user,
            $request->integer('promotion_id'),
            $request->session(),
        );

        return redirect()->back()->with('status', __('promotions.switched'));
    }
}
