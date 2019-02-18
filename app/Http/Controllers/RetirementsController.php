<?php

namespace App\Http\Controllers;

use App\Wrestler;

class RetirementsController extends Controller
{
    /**
     * Retire a wrestler.
     *
     * @param  \App\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Wrestler $wrestler)
    {
        $this->authorize('retire', Wrestler::class);

        abort_if($wrestler->isRetired(), 403);

        $wrestler->retire();

        return redirect()->route('wrestlers.index', ['state' => 'retired']);
    }

    /**
     * Unretire a retired wrestler.
     *
     * @param  \App\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Wrestler $wrestler)
    {
        $this->authorize('unretire', Wrestler::class);

        abort_unless($wrestler->isRetired(), 403);

        $wrestler->unretire();

        return redirect()->route('wrestlers.index');
    }
}
