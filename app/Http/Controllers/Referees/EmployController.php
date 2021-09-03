<?php

namespace App\Http\Controllers\Referees;

use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\EmployRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

class EmployController extends Controller
{
    /**
     * Employ a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\EmployRequest  $request
     * @param  \App\Repositories\RefereeRepository  $refereeRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, EmployRequest $request, RefereeRepository $refereeRepository)
    {
        throw_unless($referee->canBeEmployed(), new CannotBeEmployedException);

        $employmentDate = now()->toDateTimeString();

        $refereeRepository->employ($referee, $employmentDate);
        $referee->updateStatus()->save();

        return redirect()->route('referees.index');
    }
}
