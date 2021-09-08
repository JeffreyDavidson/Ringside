<?php

namespace App\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ClearInjuryRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

class ClearInjuryController extends Controller
{
    /**
     * Have a wrestler recover from an injury.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\ClearInjuryRequest  $request
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ClearInjuryRequest $request, WrestlerRepository $wrestlerRepository)
    {
        throw_unless($wrestler->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = now()->toDateTimeString();

        $wrestlerRepository->clearInjury($wrestler, $recoveryDate);
        $wrestler->updateStatus()->save();

        if (! is_null($wrestler->currentTagTeam) && $wrestler->currentTagTeam->exists()) {
            $wrestler->currentTagTeam->updateStatus()->save();
        }

        return redirect()->route('wrestlers.index');
    }
}
