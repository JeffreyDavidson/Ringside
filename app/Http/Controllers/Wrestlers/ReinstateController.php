<?php

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestler;
use App\Http\Controllers\Controller;

class ReinstateController extends Controller
{
    /**
     * Reinstate a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler)
    {
        $this->authorize('reinstate', $wrestler);

        $wrestler->reinstate();

        return redirect()->route('wrestlers.index');
    }
}
