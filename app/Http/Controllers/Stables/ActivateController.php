<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use Illuminate\Http\RedirectResponse;
use App\Actions\Stables\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Controller;
use App\Models\Stable;

class ActivateController extends Controller
{
    /**
     * Activate a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable): RedirectResponse
    {
        $this->authorize('activate', $stable);

        try {
            ActivateAction::run($stable);
        } catch (CannotBeActivatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('stables.index');
    }
}
