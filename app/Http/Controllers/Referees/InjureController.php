<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\InjureRequest;
use App\Models\Referee;

class InjureController extends Controller
{
    /**
     * Injure a referee.
     *
     * @param  App\Models\Referee  $referee
     * @param  App\Http\Requests\Referees\InjureRequest  $request
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, InjureRequest $request)
    {
        $referee->injure();

        return redirect()->route('referees.index');
    }
}
