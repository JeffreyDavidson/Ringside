<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ClearInjuryRequest;
use App\Models\Referee;

class ClearInjuryController extends Controller
{
    /**
     * Clear a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\ClearInjuryRequest  $request
     * @param  \App\Actions\Referees\ClearInjuryAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ClearInjuryRequest $request, ClearInjuryAction $action)
    {
        throw_unless($referee->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $action->handle($referee);

        return redirect()->route('referees.index');
    }
}
