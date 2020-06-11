<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\EmployRequest;
use App\Models\Referee;

class EmployController extends Controller
{
    /**
     * Employ a referee.
     *
     * @param  App\Models\Referee  $referee
     * @param  App\Http\Requests\Referees\EmployRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, EmployRequest $request)
    {
        $referee->employ();

        return redirect()->route('referees.index');
    }
}
