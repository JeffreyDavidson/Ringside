<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\EmployRequest;
use App\Models\Wrestler;

class EmployController extends Controller
{
    /**
     * Employ a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @param  App\Http\Requests\Wrestlers\EmployRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, EmployRequest $request)
    {
        $wrestler->employ();

        return redirect()->route('wrestlers.index');
    }
}
