<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\RetireRequest;
use App\Models\Referee;

class RetireController extends Controller
{
    /**
     * Retire a referee.
     *
     * @param  App\Models\Referee  $referee
     * @param  App\Http\Requests\Referees\RetireRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, RetireRequest $request)
    {
        $referee->retire();

        return redirect()->route('referees.index');
    }
}
