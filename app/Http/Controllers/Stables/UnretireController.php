<?php

namespace App\Http\Controllers\Stables;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\UnretireRequest;
use App\Models\Stable;
use App\Repositories\StableRepository;

class UnretireController extends Controller
{
    /**
     * Unretire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\UnretireRequest  $request
     * @param  \App\Repositories\StableRepository  $stableRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, UnretireRequest $request, StableRepository $stableRepository)
    {
        throw_unless($stable->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = now()->ToDateTimeString();

        $stableRepository->unretire($stable, $unretiredDate);
        $stableRepository->activate($stable, $unretiredDate);
        $stable->updateStatus()->save();

        return redirect()->route('stables.index');
    }
}
