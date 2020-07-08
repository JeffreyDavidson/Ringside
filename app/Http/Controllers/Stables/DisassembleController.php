<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\DisassembleRequest;
use App\Models\Stable;

class DisassembleController extends Controller
{
    /**
     * Disassemble a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\DisassembleRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, DisassembleRequest $request)
    {
        $this->authorize('disassemble', $stable);

        $stable->disassemble();

        return redirect()->route('stables.index');
    }
}
