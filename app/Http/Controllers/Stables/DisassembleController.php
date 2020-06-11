<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use App\Http\Controllers\Controller;

class DisassembleController extends Controller
{
    /**
     * Disassemble a stable.
     *
     * @param  App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable)
    {
        $this->authorize('disassemble', $stable);

        $stable->disassemble();

        return redirect()->route('stables.index');
    }
}
