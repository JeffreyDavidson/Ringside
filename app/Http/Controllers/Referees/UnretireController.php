<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Models\Referee;

class UnretireController extends Controller
{
    /**
     * Unretire a referee.
     *
     * @param  \App\Models\Referee  $referee
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('unretire', $referee);

        throw_unless($referee->canBeUnretired(), new CannotBeUnretiredException);

        UnretireAction::run($referee);

        return redirect()->route('referees.index');
    }
}
