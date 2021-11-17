<?php

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ReinstateRequest;
use App\Models\Wrestler;

class ReinstateController extends Controller
{
    /**
     * Reinstate a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\ReinstateRequest  $request
     * @param  \App\Actions\Wrestlers\ReinstateAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ReinstateRequest $request, ReinstateAction $action)
    {
        throw_unless($wrestler->canBeReinstated(), new CannotBeReinstatedException);

        $action->handle($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
