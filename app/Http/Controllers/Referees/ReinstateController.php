<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReinstateController extends Controller
{
    /**
     * Reinstate a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        Gate::authorize('reinstate', $referee);

        try {
            ReinstateAction::run($referee);
        } catch (CannotBeReinstatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
