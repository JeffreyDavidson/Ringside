<?php

declare(strict_types=1);

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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler)
    {
        $this->authorize('clearFromInjury', $wrestler);

        throw_unless($wrestler->canBeClearedFromInjury(), CannotBeClearedFromInjuryException::class);

        ClearInjuryAction::run($wrestler);

        return to_route('wrestlers.index');
    }
}
