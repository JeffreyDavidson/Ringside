<?php

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\RetireRequest;
use App\Models\Stable;

class RetireController extends Controller
{
    /**
     * Retire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\RetireRequest  $request
     * @param  \App\Actions\Stables\RetireAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, RetireRequest $request, RetireAction $action)
    {
        throw_unless($stable->canBeRetired(), new CannotBeRetiredException);

        $action->handle($stable);

        return redirect()->route('stables.index');
    }
}
