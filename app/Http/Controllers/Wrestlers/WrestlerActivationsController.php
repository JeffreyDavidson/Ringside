<?php

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestler;
use App\Http\Controllers\Controller;

class WrestlerActivationsController extends Controller
{
    /**
     * Activate and inactive wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Wrestler $wrestler)
    {
        $this->authorize('activate', Wrestler::class);

        abort_if($wrestler->isActive(), 403);

        $wrestler->activate();

        return redirect()->route('wrestlers.index');
    }

    /**
     * Deactivate an active wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Wrestler $wrestler)
    {
        $this->authorize('deactivate', Wrestler::class);

        abort_unless($wrestler->isActive(), 403);

        $wrestler->deactivate();

        return redirect()->route('wrestlers.index', ['state' => 'inactive']);
    }
}
