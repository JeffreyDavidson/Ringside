<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use App\Http\Controllers\Controller;

class StableRetirementsController extends Controller
{
    /**
     * Retire a tag team.
     *
     * @param  \App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Stable $stable)
    {
        $this->authorize('retire', $stable);

        $stable->retire();

        return redirect()->route('stables.index', ['state' => 'retired']);
    }

    /**
     * Unretire a retired stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Stable $stable)
    {
        $this->authorize('unretire', $stable);

        $stable->unretire();

        return redirect()->route('stables.index');
    }
}
