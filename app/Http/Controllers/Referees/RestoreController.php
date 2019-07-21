<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;

class RestoreController extends Controller
{
    /**
     * Restore a deleted referee.
     *
     * @param  int  $refereeId
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke($refereeId)
    {
        $referee = Referee::onlyTrashed()->findOrFail($refereeId);

        $this->authorize('restore', Referee::class);

        $referee->restore();

        return redirect()->route('referees.index');
    }
}
