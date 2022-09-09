<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\RetireAction;
use App\Http\Controllers\Controller;
use App\Models\Wrestler;

class RetireController extends Controller
{
    /**
     * Retire a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler)
    {
        $this->authorize('retire', $wrestler);

        RetireAction::run($wrestler);

        return to_route('wrestlers.index');
    }
}
