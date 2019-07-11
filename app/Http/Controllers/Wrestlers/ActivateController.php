<?php

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestler;
use App\Http\Controllers\Controller;

class ActivateController extends Controller
{
    /**
     * Activate a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler)
    {
        $this->authorize('activate', $wrestler);

        $wrestler->activate();

        return redirect()->route('wrestlers.index');
    }
}
