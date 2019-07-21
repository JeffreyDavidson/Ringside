<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;

class RetireController extends Controller
{
    /**
     * Retire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('retire', $referee);

        $referee->retire();

        return redirect()->route('referees.index');
    }
}
