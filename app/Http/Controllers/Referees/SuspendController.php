<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\SuspendRequest;
use App\Models\Referee;
use App\Services\RefereeService;

class SuspendController extends Controller
{
    /**
     * Suspend a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\SuspendRequest  $request
     * @param  \App\Services\RefereeService $refereeService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, SuspendRequest $request, RefereeService $refereeService)
    {
        $refereeService->suspend($referee);

        return redirect()->route('referees.index');
    }
}
