<?php

namespace App\Http\Controllers\Referees;

use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Models\Referee;

class EmployController extends Controller
{
    /**
     * Employ a referee.
     *
     * @param  App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('employ', $referee);

        if (! $referee->canBeEmployed()) {
            throw new CannotBeEmployedException();
        }

        $referee->employ();

        return redirect()->route('referees.index');
    }
}
