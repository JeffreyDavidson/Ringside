<?php

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Controller;
use App\Models\Stable;

class ActivateController extends Controller
{
    /**
     * Activate a stable.
     *
     * @param  \App\Models\Stable  $stable
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable)
    {
        $this->authorize('activate', $stable);

        throw_unless($stable->canBeActivated(), new CannotBeActivatedException);

        ActivateAction::run($stable);

        return redirect()->route('stables.index');
    }
}
