<?php

namespace App\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\UnretireRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

class UnretireController extends Controller
{
    /**
     * Unretire a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\UnretireRequest  $request
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, UnretireRequest $request, WrestlerRepository $wrestlerRepository)
    {
        throw_unless($wrestler->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = now()->toDateTimeString();

        $wrestlerRepository->unretire($wrestler, $unretiredDate);
        $wrestlerRepository->employ($wrestler, $unretiredDate);
        $wrestler->updateStatus()->save();

        return redirect()->route('wrestlers.index');
    }
}
