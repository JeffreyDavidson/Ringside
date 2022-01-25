<?php

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\DeactivateAction;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Controller;
use App\Models\Stable;

class DeactivateController extends Controller
{
    /**
     * Deactivate a stable.
     *
     * @param  \App\Models\Stable $stable
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable)
    {
        $this->authorize('deactivate', $stable);

        throw_unless($stable->canBeDeactivated(), new CannotBeDeactivatedException);

        DeactivateAction::run($stable);

        return redirect()->route('stables.index');
    }
}
