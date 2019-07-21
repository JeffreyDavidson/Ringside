<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;

class UnretireController extends Controller
{
    /**
     * Unretire a retired referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('unretire', $referee);

        $referee->unretire();

        return redirect()->route('referees.index');
    }
}
