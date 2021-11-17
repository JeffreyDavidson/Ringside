<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ReinstateRequest;
use App\Models\Referee;

class ReinstateController extends Controller
{
    /**
     * Reinstate a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\ReinstateRequest  $request
     * @param  \App\Actions\Referees\ReinstateAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ReinstateRequest $request, ReinstateAction $action)
    {
        throw_unless($referee->canBeReinstated(), new CannotBeReinstatedException);

        $action->handle($referee);

        return redirect()->route('referees.index');
    }
}
