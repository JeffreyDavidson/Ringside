<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\UnretireRequest;
use App\Models\Referee;

class UnretireController extends Controller
{
    /**
     * Unretire a referee.
     *
     * @param  App\Models\Referee  $referee
     * @param  App\Http\Requests\Referees\UnretireRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, UnretireRequest $request)
    {
        $referee->unretire();

        return redirect()->route('referees.index');
    }
}
