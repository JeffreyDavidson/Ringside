<?php

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\DeactivateAction;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\DeactivateRequest;
use App\Models\Stable;

class DeactivateController extends Controller
{
    /**
     * Deactivate a stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  \App\Http\Requests\Stables\DeactivateRequest  $request
     * @param  \App\Actions\Stables\DeactivateAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, DeactivateRequest $request, DeactivateAction $action)
    {
        throw_unless($stable->canBeDeactivated(), new CannotBeDeactivatedException);

        $action->handle($stable);

        return redirect()->route('stables.index');
    }
}
