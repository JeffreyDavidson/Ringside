<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\SuspendRequest;
use App\Models\Referee;

class SuspendController extends Controller
{
    /**
     * Suspend a referee.
     *
     * @param  App\Models\Referee  $referee
     * @param  App\Http\Requests\Referees\SuspendRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, SuspendRequest $request)
    {
        $referee->suspend();

        return redirect()->route('referees.index');
    }
}
