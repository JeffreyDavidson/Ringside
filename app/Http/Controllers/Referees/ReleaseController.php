<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referees;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ReleaseRequest;

class ReleaseController extends Controller
{
    /**
     * Fire a referee.
     *
     * @param  App\Models\Referee  $referee
     * @param  App\Http\Requests\Referees\ReleaseRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ReleaseRequest $request)
    {
        $referee->release();

        return redirect()->route('referees.index');
    }
}
