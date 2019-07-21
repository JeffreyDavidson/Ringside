<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;

class ActivateController extends Controller
{
    /**
     * Activate a pending introduced referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        // dd($referee);
        $this->authorize('activate', $referee);

        $referee->activate();

        return redirect()->route('referees.index');
    }
}
