<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\RetireRequest;
use App\Models\Stable;

class RetireController extends Controller
{
    /**
     * Retire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\RetireRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, RetireRequest $request)
    {
        $stable->retire();

        return redirect()->route('stables.index');
    }
}
