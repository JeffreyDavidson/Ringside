<?php

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\RetireRequest;
use App\Models\Wrestler;

class RetireController extends Controller
{
    /**
     * Retire a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\RetireRequest  $request
     * @param  \App\Actions\Wrestlers\RetireAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, RetireRequest $request, RetireAction $action)
    {
        throw_unless($wrestler->canBeRetired(), new CannotBeRetiredException);

        $action->handle($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
