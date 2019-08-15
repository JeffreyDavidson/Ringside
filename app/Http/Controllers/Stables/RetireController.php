<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use App\Http\Controllers\Controller;

class RetireController extends Controller
{
    /**
     * Retire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable)
    {
        $this->authorize('retire', $stable);

        $stable->retire();

        return redirect()->route('stables.index');
    }
}
