<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use App\Http\Controllers\Controller;

class ActivateController extends Controller
{
    /**
     * Activate a stable.
     *
     * @param  App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable)
    {
        $this->authorize('activate', $stable);

        $stable->activate();

        return redirect()->route('stables.index');
    }
}
