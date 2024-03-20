<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Referee;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RestoreController extends Controller
{
    /**
     * Restore a deleted referee.
     */
    public function __invoke(int $refereeId): RedirectResponse
    {
        $referee = Referee::onlyTrashed()->findOrFail($refereeId);

        Gate::authorize('restore', Referee::class);

        try {
            RestoreAction::run($referee);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
