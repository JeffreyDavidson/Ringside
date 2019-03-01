<?php

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestler;
use App\Http\Controllers\Controller;

class WrestlerInjuriesController extends Controller
{
    /**
     * Create an injury for the wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
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
     * @param  \App\Models\Wrestler  $wrestler
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
