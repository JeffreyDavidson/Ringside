<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Models\Referee;

class ReinstateController extends Controller
{
    /**
     * Reinstate a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('reinstate', $referee);

        throw_unless($referee->canBeReinstated(), CannotBeReinstatedException::class);

        ReinstateAction::run($referee);

        return redirect()->route('referees.index');
    }
}
