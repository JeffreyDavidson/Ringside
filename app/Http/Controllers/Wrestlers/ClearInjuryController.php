<?php

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Controller;
use App\Models\Wrestler;

class ClearInjuryController extends Controller
{
    /**
     * Have a wrestler recover from an injury.
     *
     * @param  \App\Models\Wrestler  $wrestler
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler)
    {
        $this->authorize('clearFromInjury', $wrestler);

        throw_unless($wrestler->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        ClearInjuryAction::run($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
