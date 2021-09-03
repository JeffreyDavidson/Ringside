<?php

namespace App\Http\Controllers\Referees;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\UnretireRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

class UnretireController extends Controller
{
    /**
     * Unretire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\UnretireRequest  $request
     * @param  \App\Repositories\RefereeRepository  $refereeRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, UnretireRequest $request, RefereeRepository $refereeRepository)
    {
        throw_unless($referee->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = now()->toDateTimeString();

        $refereeRepository->unretire($referee, $unretiredDate);
        $refereeRepository->employ($referee, $unretiredDate);
        $referee->updateStatus()->save();

        return redirect()->route('referees.index');
    }
}
