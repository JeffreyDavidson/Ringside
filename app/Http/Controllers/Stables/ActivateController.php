<?php

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\ActivateRequest;
use App\Models\Stable;

class ActivateController extends Controller
{
    /**
     * Activate a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\ActivateRequest  $request
     * @param  \App\Actions\Stables\ActivateAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, ActivateRequest $request, ActivateAction $action)
    {
        throw_unless($stable->canBeActivated(), new CannotBeActivatedException);

        $action->handle($stable);

        return redirect()->route('stables.index');
    }
}
