<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;

class RefereeActivationsController extends Controller
{
    /**
     * Activate and inactive referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Referee $referee)
    {
        $this->authorize('activate', $referee);

        $referee->activate();

        return redirect()->route('referees.index');
    }

    /**
     * Deactivate an active referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Referee $referee)
    {
        $this->authorize('deactivate', $referee);

        $referee->deactivate();

        return redirect()->route('referees.index', ['state' => 'inactive']);
    }
}
