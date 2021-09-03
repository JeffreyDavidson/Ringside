<?php

namespace App\Http\Controllers\Referees;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\ClearInjuryRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

class ClearInjuryController extends Controller
{
    /**
     * Clear a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\ClearInjuryRequest  $request
     * @param  \App\Repositories\RefereeRepository  $refereeRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, ClearInjuryRequest $request, RefereeRepository $refereeRepository)
    {
        throw_unless($referee->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = now()->toDateTimeString();

        $refereeRepository->clearInjury($referee, $recoveryDate);
        $referee->updateStatus()->save();

        return redirect()->route('referees.index');
    }
}
