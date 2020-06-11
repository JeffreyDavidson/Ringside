<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\InjureRequest;
use App\Models\Wrestler;

class InjureController extends Controller
{
    /**
     * Injure a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @param  App\Http\Requests\Wrestlers\InjureRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, InjureRequest $request)
    {
        $wrestler->injure();

        return redirect()->route('wrestlers.index');
    }
}
