<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Models\Referee;

class RetireController extends Controller
{
    /**
     * Retire a referee.
     *
     * @param  \App\Models\Referee  $referee
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('retire', $referee);

        throw_unless($referee->canBeRetired(), CannotBeRetiredException::class);

        RetireAction::run($referee);

        return redirect()->route('referees.index');
    }
}
