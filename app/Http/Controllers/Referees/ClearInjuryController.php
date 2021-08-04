<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ClearInjuryRequest;
use App\Models\Referee;
use App\Services\RefereeService;

class ClearInjuryController extends Controller
{
    /**
     * Clear a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\ClearInjuryRequest  $request
     * @param  \App\Services\RefereeService $refereeService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ClearInjuryRequest $request, RefereeService $refereeService)
    {
        $refereeService->clearFromInjury($referee);

        return redirect()->route('referees.index');
    }
}
