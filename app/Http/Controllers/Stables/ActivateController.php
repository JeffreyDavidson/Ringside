<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Controller;
use App\Models\Stable;
use Illuminate\Http\RedirectResponse;

class ActivateController extends Controller
{
    /**
     * Activate a stable.
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
