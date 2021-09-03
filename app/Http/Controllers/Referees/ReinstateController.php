<?php

namespace App\Http\Controllers\Referees;

use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ReinstateRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

class ReinstateController extends Controller
{
    /**
     * Reinstate a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\ReinstateRequest  $request
     * @param  \App\Repositories\RefereeRepository  $refereeRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ReinstateRequest $request, RefereeRepository $refereeRepository)
    {
        throw_unless($referee->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatementDate = now()->toDateTimeString();

        $refereeRepository->reinstate($referee, $reinstatementDate);
        $referee->updateStatus()->save();

        return redirect()->route('referees.index');
    }
}
