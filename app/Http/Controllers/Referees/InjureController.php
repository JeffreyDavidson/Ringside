<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;

class InjureController extends Controller
{
    /**
     * Create an injury for the referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('injure', $referee);

        $referee->injure();

        return redirect()->route('referees.index');
    }
}
