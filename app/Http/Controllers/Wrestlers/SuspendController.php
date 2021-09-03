<?php

namespace App\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\SuspendRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

class SuspendController extends Controller
{
    /**
     * Suspend a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\SuspendRequest  $request
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, SuspendRequest $request, WrestlerRepository $wrestlerRepository)
    {
        throw_unless($wrestler->canBeSuspended(), new CannotBeSuspendedException);

        $suspensionDate = now()->toDateTimeString();

        $wrestlerRepository->suspend($wrestler, $suspensionDate);
        $wrestler->updateStatus()->save();

        if (! is_null($wrestler->currentTagTeam) && $wrestler->currentTagTeam->exists()) {
            $wrestler->currentTagTeam->updateStatus()->save();
        }

        return redirect()->route('wrestlers.index');
    }
}
