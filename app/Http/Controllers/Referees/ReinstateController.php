<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ReinstateRequest;
use App\Models\Referee;

class ReinstateController extends Controller
{
    /**
     * Reinstate a referee.
     *
     * @param  App\Models\Referee  $referee
     * @param  App\Http\Requests\Referees\ReinstateRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ReinstateRequest $request)
    {
        $referee->reinstate();

        return redirect()->route('referees.index');
    }
}
