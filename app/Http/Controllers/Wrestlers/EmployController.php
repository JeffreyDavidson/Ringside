<?php

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Models\Wrestler;

class EmployController extends Controller
{
    /**
     * Employ a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler)
    {
        $this->authorize('employ', $wrestler);

        throw_unless($wrestler->canBeEmployed(), CannotBeEmployedException::class);

        EmployAction::run($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
