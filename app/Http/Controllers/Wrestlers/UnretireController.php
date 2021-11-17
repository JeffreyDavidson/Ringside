<?php

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\UnretireRequest;
use App\Models\Wrestler;

class UnretireController extends Controller
{
    /**
     * Unretire a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\UnretireRequest  $request
     * @param  \App\Actions\Wrestlers\UnretireAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, UnretireRequest $request, UnretireAction $action)
    {
        throw_unless($wrestler->canBeUnretired(), new CannotBeUnretiredException);

        $action->handle($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
