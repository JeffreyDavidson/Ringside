<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\InjureRequest;
use App\Models\Referee;
use App\Services\RefereeService;

class InjureController extends Controller
{
    /**
     * Injure a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\InjureRequest  $request
     * @param  \App\Services\RefereeService $refereeService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, InjureRequest $request, RefereeService $refereeService)
    {
        $refereeService->injure($referee);

        return redirect()->route('referees.index');
    }
}
