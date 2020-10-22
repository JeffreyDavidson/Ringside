<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ReleaseRequest;
use App\Models\Wrestler;

class ReleaseController extends Controller
{
    /**
     * Release a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\ReleaseRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ReleaseRequest $request)
    {
        $wrestler->release();

        if ($wrestler->currentTagTeam) {
            $wrestler->currentTagTeam->updateStatusAndSave();
        }

        return redirect()->route('wrestlers.index');
    }
}
