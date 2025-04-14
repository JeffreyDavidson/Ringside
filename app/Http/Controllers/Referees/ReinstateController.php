<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ReinstateController
{
    /**
     * Reinstate a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        Gate::authorize('reinstate', $referee);

        try {
            resolve(ReinstateAction::class)->handle($referee);
        } catch (CannotBeReinstatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
