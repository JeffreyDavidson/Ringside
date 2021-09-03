<?php

namespace App\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\RetireRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

class RetireController extends Controller
{
    /**
     * Retire a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\RetireRequest  $request
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, RetireRequest $request, WrestlerRepository $wrestlerRepository)
    {
        throw_unless($wrestler->canBeRetired(), new CannotBeRetiredException);

        $retirementDate = now()->toDateTimeString();

        if ($wrestler->isSuspended()) {
            $wrestlerRepository->reinstate($wrestler, $retirementDate);
        }

        if ($wrestler->isInjured()) {
            $wrestlerRepository->clearInjury($wrestler, $retirementDate);
        }

        $wrestlerRepository->release($wrestler, $retirementDate);
        $wrestlerRepository->retire($wrestler, $retirementDate);

        $wrestler->updateStatus()->save();

        if (! is_null($wrestler->currentTagTeam) && $wrestler->currentTagTeam->exists()) {
            $wrestler->currentTagTeam->updateStatus()->save();
        }

        return redirect()->route('wrestlers.index');
    }
}
