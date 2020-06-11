<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\RetireRequest;
use App\Models\Wrestler;

class RetireController extends Controller
{
    /**
     * Retire a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @param  App\Http\Requests\Wrestlers\RetireRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, RetireRequest $request)
    {
        $wrestler->retire();

        return redirect()->route('wrestlers.index');
    }
}
