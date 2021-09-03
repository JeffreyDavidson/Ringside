<?php

namespace App\Http\Controllers\Stables;

use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\DeactivateRequest;
use App\Models\Stable;
use App\Repositories\StableRepository;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

class DeactivateController extends Controller
{
    /**
     * Deactivates a stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  \App\Http\Requests\Stables\DeactivateRequest  $request
     * @param  \App\Repositories\StableRepository  $stableRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(
        Stable $stable,
        DeactivateRequest $request,
        StableRepository $stableRepository,
    ) {
        throw_unless($stable->canBeDeactivated(), new CannotBeDeactivatedException);

        $deactivationDate = now()->toDateTimeString();

        $stableRepository->deactivate($stable, $deactivationDate);
        $stableRepository->disassemble($stable, $deactivationDate);
        $stable->updateStatus()->save();

        return redirect()->route('stables.index');
    }
}
