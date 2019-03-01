<?php

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestler;
use App\Http\Controllers\Controller;

class WrestlerSuspensionsController extends Controller
{
    /**
     * Suspend a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Wrestler $wrestler)
    {
        $this->authorize('suspend', $wrestler);

        $wrestler->suspend();

        return redirect()->route('wrestlers.index', ['state' => 'suspended']);
    }

    /**
     * Reinstate a suspended wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Wrestler $wrestler)
    {
        $this->authorize('reinstate', $wrestler);

        $wrestler->reinstate();

        return redirect()->route('wrestlers.index');
    }
}
