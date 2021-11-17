<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\RetireRequest;
use App\Models\Referee;

class RetireController extends Controller
{
    /**
     * Retire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\RetireRequest  $request
     * @param  \App\Actions\Referees\RetireAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, RetireRequest $request, RetireAction $action)
    {
        throw_unless($referee->canBeRetired(), new CannotBeRetiredException);

        $action->handle($referee);

        return redirect()->route('referees.index');
    }
}
