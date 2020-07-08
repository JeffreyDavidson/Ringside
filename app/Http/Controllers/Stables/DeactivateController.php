<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\DeactivateRequest;
use App\Models\Stable;

class DeactivateController extends Controller
{
    /**
     * Deactivates a stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  \App\Http\Requests\Stables\DeactivateRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, DeactivateRequest $request)
    {
        $stable->deactivate();

        return redirect()->route('stables.index');
    }
}
