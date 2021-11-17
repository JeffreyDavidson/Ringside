<?php

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ClearInjuryRequest;
use App\Models\Wrestler;

class ClearInjuryController extends Controller
{
    /**
     * Have a wrestler recover from an injury.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\ClearInjuryRequest  $request
     * @param  \App\Actions\Wrestlers\ClearInjuryAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ClearInjuryRequest $request, ClearInjuryAction $action)
    {
        throw_unless($wrestler->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $action->handle($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
