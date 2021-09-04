<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Models\Wrestler;

class RestoreController extends Controller
{
    /**
     * Restore a deleted wrestler.
     *
     * @param  int  $wrestlerId
     * @param  \App\Services\WrestlerService $wrestlerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke($wrestlerId, Wrestlerervice $wrestlerService)
    {
        $wrestler = Wrestler::onlyTrashed()->findOrFail($wrestlerId);

        $this->authorize('restore', $wrestler);

        $wrestlerService->restore($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
