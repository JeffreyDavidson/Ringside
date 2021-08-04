<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\DisassembleRequest;
use App\Models\Stable;
use App\Services\StableService;

class DisassembleController extends Controller
{
    /**
     * Disassemble a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\DisassembleRequest  $request
     * @param  \App\Services\StableService $stableService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, DisassembleRequest $request, StableService $stableService)
    {
        $stableService->disassemble($stable);

        return redirect()->route('stables.index');
    }
}
