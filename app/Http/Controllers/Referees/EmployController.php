<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\EmployRequest;
use App\Models\Referee;
use App\Services\RefereeService;

class EmployController extends Controller
{
    /**
     * Employ a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\EmployRequest  $request
     * @param  \App\Services\RefereeService $refereeService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, EmployRequest $request, RefereeService $refereeService)
    {
        $refereeService->employ($referee);

        return redirect()->route('referees.index');
    }
}
