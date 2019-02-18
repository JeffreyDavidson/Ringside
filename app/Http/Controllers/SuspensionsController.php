<?php

namespace App\Http\Controllers;

use App\Wrestler;

class SuspensionsController extends Controller
{
    /**
     * Suspend a wrestler.
     *
     * @param  \App\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Wrestler $wrestler)
    {
        $this->authorize('suspend', Wrestler::class);

        abort_if($wrestler->isSuspended(), 403);

        $wrestler->suspend();

        return redirect()->route('wrestlers.index', ['state' => 'suspended']);
    }

    /**
     * Reinstate a suspended wrestler.
     *
     * @param  \App\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Wrestler $wrestler)
    {
        $this->authorize('reinstate', Wrestler::class);

        abort_unless($wrestler->isSuspended(), 403);

        $wrestler->reinstate();

        return redirect()->route('wrestlers.index');
    }
}
