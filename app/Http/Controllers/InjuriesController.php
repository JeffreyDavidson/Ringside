<?php

namespace App\Http\Controllers;

use App\Wrestler;

class InjuriesController extends Controller
{
    /**
     * Create an injury for the wrestler.
     *
     * @param  \App\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Wrestler $wrestler)
    {
        $this->authorize('injure', Wrestler::class);

        abort_if($wrestler->isInjured(), 403);

        $wrestler->injure();

        return redirect()->route('wrestlers.index', ['state' => 'injured']);
    }

    /**
     * Recover an injured wrestler.
     *
     * @param  \App\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Wrestler $wrestler)
    {
        $this->authorize('recover', Wrestler::class);

        abort_unless($wrestler->isInjured(), 403);

        $wrestler->recover();

        return redirect()->route('wrestlers.index');
    }
}
