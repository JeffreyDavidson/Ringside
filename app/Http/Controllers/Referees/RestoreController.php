<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;
use App\Services\RefereeService;

class RestoreController extends Controller
{
    /**
     * Restore a deleted referee.
     *
     * @param  int  $refereeId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke($refereeId, RefereeService $refereeService)
    {
        $referee = Referee::onlyTrashed()->findOrFail($refereeId);

        $this->authorize('restore', Referee::class);

        $refereeService->restore($referee);

        return redirect()->route('referees.index');
    }
}
