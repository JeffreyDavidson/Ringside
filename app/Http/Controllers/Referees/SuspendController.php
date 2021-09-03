<?php

namespace App\Http\Controllers\Referees;

use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\SuspendRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

class SuspendController extends Controller
{
    /**
     * Suspend a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\SuspendRequest  $request
     * @param  \App\Repositories\RefereeRepository  $refereeRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, SuspendRequest $request, RefereeRepository $refereeRepository)
    {
        throw_unless($referee->canBeSuspended(), new CannotBeSuspendedException);

        $suspensionDate = now()->toDateTimeString();

        $refereeRepository->suspend($referee, $suspensionDate);
        $referee->updateStatus()->save();

        return redirect()->route('referees.index');
    }
}
