<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\SuspendRequest;
use App\Models\Wrestler;

class SuspendController extends Controller
{
    /**
     * Suspend a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @param  App\Http\Requests\Wrestlers\SuspendRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, SuspendRequest $request)
    {
        $wrestler->suspend();

        return redirect()->route('wrestlers.index');
    }
}
