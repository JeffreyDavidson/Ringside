<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;

class RefereeRetirementsController extends Controller
{
    /**
     * Retire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Referee $referee)
    {
        $this->authorize('retire', $referee);

        $referee->retire();

        return redirect()->route('referees.index', ['state' => 'retired']);
    }

    /**
     * Unretire a retired referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Referee $referee)
    {
        $this->authorize('unretire', $referee);

        $referee->unretire();

        return redirect()->route('referees.index');
    }
}
