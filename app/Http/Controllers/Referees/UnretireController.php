<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\UnretireRequest;
use App\Models\Referee;
use App\Services\RefereeService;

class UnretireController extends Controller
{
    /**
     * Unretire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\UnretireRequest  $request
     * @param  \App\Services\RefereeService $refereeService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, UnretireRequest $request, RefereeService $refereeService)
    {
        $refereeService->unretire($referee);

        return redirect()->route('referees.index');
    }
}
