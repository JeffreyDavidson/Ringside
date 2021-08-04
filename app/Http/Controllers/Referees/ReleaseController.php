<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ReleaseRequest;
use App\Models\Referee;
use App\Services\RefereeService;

class ReleaseController extends Controller
{
    /**
     * Fire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\ReleaseRequest  $request
     * @param  \App\Services\RefereeService $refereeService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ReleaseRequest $request, RefereeService $refereeService)
    {
        $refereeService->release($referee);

        return redirect()->route('referees.index');
    }
}
