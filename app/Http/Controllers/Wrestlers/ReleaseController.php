<?php

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ReleaseRequest;
use App\Models\Wrestler;

class ReleaseController extends Controller
{
    /**
     * Release a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\ReleaseRequest  $request
     * @param  \App\Actions\Wrestlers\ReleaseAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ReleaseRequest $request, ReleaseAction $action)
    {
        throw_unless($wrestler->canBeReleased(), new CannotBeReleasedException);

        $action->handle($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
