<?php

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\UnretireRequest;
use App\Models\Stable;

class UnretireController extends Controller
{
    /**
     * Unretire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\UnretireRequest  $request
     * @param  \App\Actions\Stables\UnretireAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, UnretireRequest $request, UnretireAction $action)
    {
        throw_unless($stable->canBeUnretired(), new CannotBeUnretiredException);

        $action->handle($stable);

        return redirect()->route('stables.index');
    }
}
