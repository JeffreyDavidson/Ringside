<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ClearInjuryRequest;
use App\Models\Referee;

class ClearInjuryController extends Controller
{
    /**
     * Clear a referee.
     *
     * @param  App\Models\Referee  $referee
     * @param  App\Http\Requests\Referees\ClearInjuryRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ClearInjuryRequest $request)
    {
        $referee->clearFromInjury();

        return redirect()->route('referees.index');
    }
}
