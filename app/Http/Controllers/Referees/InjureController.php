<?php

namespace App\Http\Controllers\Referees;

use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\InjureRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

class InjureController extends Controller
{
    /**
     * Injure a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Http\Requests\Referees\InjureRequest  $request
     * @param  \App\Repositories\RefereeRepository $refereeRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee, InjureRequest $request, RefereeRepository $refereeRepository)
    {
        throw_unless($referee->canBeInjured(), new CannotBeInjuredException);

        $injureDate = now()->toDateTimeString();

        $refereeRepository->injure($referee, $injureDate);
        $referee->updateStatus()->save();

        return redirect()->route('referees.index');
    }
}
