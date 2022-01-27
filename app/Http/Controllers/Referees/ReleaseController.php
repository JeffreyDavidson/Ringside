<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Models\Referee;

class ReleaseController extends Controller
{
    /**
     * Release a referee.
     *
     * @param  \App\Models\Referee  $referee
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('release', $referee);

        throw_unless($referee->canBeReleased(), CannotBeReleasedException::class);

        ReleaseAction::run($referee);

        return redirect()->route('referees.index');
    }
}
