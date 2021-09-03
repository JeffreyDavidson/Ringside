<?php

namespace App\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\EmployRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

class EmployController extends Controller
{
    /**
     * Employ a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\EmployRequest  $request
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, EmployRequest $request, WrestlerRepository $wrestlerRepository)
    {
        throw_unless($wrestler->canBeEmployed(), new CannotBeEmployedException);

        $employmentDate = now()->toDateTimeString();

        $wrestlerRepository->employ($wrestler, $employmentDate);
        $wrestler->updateStatus()->save();

        return redirect()->route('wrestlers.index');
    }
}
