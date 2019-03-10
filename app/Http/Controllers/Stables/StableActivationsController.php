<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use App\Http\Controllers\Controller;

class StableActivationsController extends Controller
{
    /**
     * Activate and inactive stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Stable $stable)
    {
        $this->authorize('activate', $stable);

        $stable->activate();

        return redirect()->route('stables.index');
    }

    /**
     * Deactivate an active stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Stable $stable)
    {
        $this->authorize('deactivate', $stable);

        $stable->deactivate();

        return redirect()->route('stables.index', ['state' => 'inactive']);
    }
}
