<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use App\Http\Controllers\Controller;

class StableSuspensionsController extends Controller
{
    /**
     * Suspend a tag team.
     *
     * @param  \App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Stable $stable)
    {
        $this->authorize('suspend', $stable);

        $stable->suspend();

        return redirect()->route('stables.index', ['state' => 'suspended']);
    }

    /**
     * Reinstate a suspended tag team.
     *
     * @param  \App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Stable $stable)
    {
        $this->authorize('reinstate', $stable);

        $stable->reinstate();

        return redirect()->route('stables.index');
    }
}
