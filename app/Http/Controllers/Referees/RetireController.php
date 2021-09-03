<?php

namespace App\Http\Controllers\Referees;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\RetireRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

class RetireController extends Controller
{
    /**
     * Retire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\RetireRequest  $request
     * @param  \App\Repositories\RefereeRepository  $refereeRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, RetireRequest $request, RefereeRepository $refereeRepository)
    {
        throw_unless($referee->canBeRetired(), new CannotBeRetiredException);

        $retirementDate = now()->toDateTimeString();

        if ($referee->isSuspended()) {
            $refereeRepository->reinstate($referee, $retirementDate);
        }

        if ($referee->isInjured()) {
            $refereeRepository->clearInjury($referee, $retirementDate);
        }

        $refereeRepository->release($referee, $retirementDate);
        $refereeRepository->retire($referee, $retirementDate);
        $referee->updateStatus()->save();

        return redirect()->route('referees.index');
    }
}
