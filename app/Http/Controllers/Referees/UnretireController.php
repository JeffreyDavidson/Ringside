<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\UnretireRequest;
use App\Models\Referee;

class UnretireController extends Controller
{
    /**
     * Unretire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\UnretireRequest  $request
     * @param  \App\Actions\Referees\UnretireAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, UnretireRequest $request, UnretireAction $action)
    {
        throw_unless($referee->canBeUnretired(), new CannotBeUnretiredException);

        $action->handle($referee);

        return redirect()->route('referees.index');
    }
}
