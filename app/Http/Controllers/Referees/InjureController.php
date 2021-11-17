<?php

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\InjureRequest;
use App\Models\Referee;

class InjureController extends Controller
{
    /**
     * Injure a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\InjureRequest  $request
     * @param  \App\Actions\Referees\InjureAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, InjureRequest $request, InjureAction $action)
    {
        throw_unless($referee->canBeInjured(), new CannotBeInjuredException);

        $action->handle($referee);

        return redirect()->route('referees.index');
    }
}
