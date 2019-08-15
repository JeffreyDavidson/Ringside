<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use App\Http\Controllers\Controller;

class RestoreController extends Controller
{
    /**
     * Restore a stable.
     *
     * @param  int  $stableId
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke($stableId)
    {
        $stable = Stable::onlyTrashed()->findOrFail($stableId);

        $this->authorize('restore', $stable);

        $stable->restore();

        return redirect()->route('stables.index');
    }
}
