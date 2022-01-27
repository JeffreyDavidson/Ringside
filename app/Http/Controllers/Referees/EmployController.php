<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Models\Referee;

class EmployController extends Controller
{
    /**
     * Employ a referee.
     *
     * @param  \App\Models\Referee  $referee
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('employ', $referee);

        throw_unless($referee->canBeEmployed(), CannotBeEmployedException::class);

        EmployAction::run($referee);

        return redirect()->route('referees.index');
    }
}
