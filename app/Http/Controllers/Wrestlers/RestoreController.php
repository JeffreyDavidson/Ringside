<?php

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestler;
use App\Http\Controllers\Controller;

class RestoreController extends Controller
{
    /**
     * Restore a deleted wrestler.
     *
     * @param  int  $wrestlerId
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke($wrestlerId)
    {
        $wrestler = Wrestler::onlyTrashed()->findOrFail($wrestlerId);

        $this->authorize('restore', $wrestler);

        $wrestler->restore();

        return redirect()->route('wrestlers.index');
    }
}
