<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;

class RecoverController extends Controller
{
    /**
     * Recover an injured referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('recover', $referee);

        $referee->recover();

        return redirect()->route('referees.index');
    }
}
