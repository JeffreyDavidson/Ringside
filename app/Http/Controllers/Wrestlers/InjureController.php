<?php

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\InjureRequest;
use App\Models\Wrestler;

class InjureController extends Controller
{
    /**
     * Injure a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\InjureRequest  $request
     * @param  \App\Actions\Wrestlers\InjureAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, InjureRequest $request, InjureAction $action)
    {
        throw_unless($wrestler->canBeInjured(), new CannotBeInjuredException);

        $action->handle($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
