<?php

namespace App\Http\Controllers;

use App\Referee;

class RefereeActivationsController extends Controller
{
    /**
     * Activate and inactive referee.
     *
     * @param  \App\Referee  $referee
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
     * @param  \App\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Referee $referee)
    {
        $this->authorize('deactivate', $referee);

        $referee->deactivate();

        return redirect()->route('referees.index', ['state' => 'inactive']);
    }
}
