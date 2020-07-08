<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\ActivateRequest;
use App\Models\Stable;

class ActivateController extends Controller
{
    /**
     * Activate a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\ActivateRequest  $stable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, ActivateRequest $request)
    {
        $stable->activate();

        return redirect()->route('stables.index');
    }
}
