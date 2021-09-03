<?php

namespace App\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ReinstateRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

class ReinstateController extends Controller
{
    /**
     * Reinstate a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\ReinstateRequest  $request
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ReinstateRequest $request, WrestlerRepository $wrestlerRepository)
    {
        throw_unless($wrestler->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatementDate = now()->toDateTimeString();

        $wrestlerRepository->reinstate($wrestler, $reinstatementDate);
        $wrestler->updateStatus()->save();

        if (! is_null($wrestler->currentTagTeam) && $wrestler->currentTagTeam->exists()) {
            $wrestler->currentTagTeam->updateStatus()->save();
        }

        return redirect()->route('wrestlers.index');
    }
}
