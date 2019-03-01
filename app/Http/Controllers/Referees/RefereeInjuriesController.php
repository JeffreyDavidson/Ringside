<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;

class RefereeInjuriesController extends Controller
{
    /**
     * Create an injury for the referee.
     *
     * @param  \App\Models\Referee  $referee
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
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Referee $referee)
    {
        $this->authorize('recover', $referee);

        $referee->recover();

        return redirect()->route('referees.index');
    }
}
