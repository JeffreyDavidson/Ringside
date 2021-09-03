<?php

namespace App\Http\Controllers\Referees;

use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ReleaseRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

class ReleaseController extends Controller
{
    /**
     * Fire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\ReleaseRequest  $request
     * @param  \App\Repositories\RefereeRepository  $refereeRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ReleaseRequest $request, RefereeRepository $refereeRepository)
    {
        throw_unless($referee->canBeReleased(), new CannotBeReleasedException);

        $releaseDate = now()->toDateTimeString();

        if ($referee->isSuspended()) {
            $refereeRepository->reinstate($referee, $releaseDate);
        }

        if ($referee->isInjured()) {
            $refereeRepository->clearInjury($referee, $releaseDate);
        }

        $refereeRepository->release($referee, $releaseDate);
        $referee->updateStatus()->save();

        return redirect()->route('referees.index');
    }
}
