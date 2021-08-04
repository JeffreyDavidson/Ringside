<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ReinstateRequest;
use App\Models\Referee;
use App\Services\RefereeService;

class ReinstateController extends Controller
{
    /**
     * Reinstate a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\ReinstateRequest  $request
     * @param  \App\Services\RefereeService $refereeService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ReinstateRequest $request, RefereeService $refereeService)
    {
        $refereeService->reinstate($referee);

        return redirect()->route('referees.index');
    }
}
