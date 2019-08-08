<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use App\Http\Controllers\Controller;

class UnretireController extends Controller
{
    /**
     * Unretire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable)
    {
        $this->authorize('unretire', $stable);

        $stable->unretire();

        return redirect()->route('roster.stables.index');
    }
}
