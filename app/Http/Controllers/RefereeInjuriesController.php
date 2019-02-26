<?php

namespace App\Http\Controllers;

use App\Referee;

class RefereeInjuriesController extends Controller
{
    /**
     * Create an injury for the referee.
     *
     * @param  \App\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Referee $referee)
    {
        $this->authorize('injure', $referee);

        $referee->injure();

        return redirect()->route('referees.index', ['state' => 'injured']);
    }

    /**
     * Recover an injured referee.
     *
     * @param  \App\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Referee $referee)
    {
        $this->authorize('recover', $referee);

        $referee->recover();

        return redirect()->route('referees.index');
    }
}
