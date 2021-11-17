<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\EmployRequest;
use App\Models\Referee;

class EmployController extends Controller
{
    /**
     * Employ a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\EmployRequest  $request
     * @param  \App\Actions\Referees\EmployAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, EmployRequest $request, EmployAction $action)
    {
        throw_unless($referee->canBeEmployed(), new CannotBeEmployedException);

        $action->handle($referee);

        return redirect()->route('referees.index');
    }
}
