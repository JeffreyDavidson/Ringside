<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\RetireRequest;
use App\Models\Referee;
use App\Services\RefereeService;

class RetireController extends Controller
{
    /**
     * Retire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\RetireRequest  $request
     * @param  \App\Services\RefereeService $refereeService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, RetireRequest $request, RefereeService $refereeService)
    {
        $refereeService->retire($referee);

        return redirect()->route('referees.index');
    }
}
