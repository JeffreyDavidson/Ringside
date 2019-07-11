<?php

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestler;
use App\Http\Controllers\Controller;

class RecoverController extends Controller
{
    /**
     * Recover a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler)
    {
        $this->authorize('recover', $wrestler);

        $wrestler->recover();

        return redirect()->route('wrestlers.index');
    }
}
