<?php

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ReleaseRequest;

class ReleaseController extends Controller
{
    /**
     * Fire a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @param  App\Http\Requests\Wrestlers\ReleaseRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ReleaseRequest $request)
    {
        $wrestler->release();

        return redirect()->route('wrestlers.index');
    }
}
