<?php

namespace App\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ReleaseRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

class ReleaseController extends Controller
{
    /**
     * Release a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\ReleaseRequest  $request
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ReleaseRequest $request, WrestlerRepository $wrestlerRepository)
    {
        throw_unless($wrestler->canBeReleased(), new CannotBeReleasedException);

        $releaseDate = $releaseDate ?? now()->toDateTimeString();

        if ($wrestler->isSuspended()) {
            $wrestlerRepository->reinstate($wrestler, $releaseDate);
        }

        if ($wrestler->isInjured()) {
            $wrestlerRepository->clearInjury($wrestler, $releaseDate);
        }

        $wrestlerRepository->release($wrestler, $releaseDate);
        $wrestler->updateStatus()->save();

        if (! is_null($wrestler->currentTagTeam) && $wrestler->currentTagTeam->exists()) {
            $wrestler->currentTagTeam->updateStatus()->save();
        }

        return redirect()->route('wrestlers.index');
    }
}
