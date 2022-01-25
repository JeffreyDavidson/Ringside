<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Controller;
use App\Models\Referee;

class SuspendController extends Controller
{
    /**
     * Suspend a referee.
     *
     * @param  \App\Models\Referee  $referee
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('suspend', $referee);

        throw_unless($referee->canBeSuspended(), new CannotBeSuspendedException);

        SuspendAction::run($referee);

        return redirect()->route('referees.index');
    }
}
