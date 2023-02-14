<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use Illuminate\Http\RedirectResponse;
use App\Actions\Stables\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Models\Stable;

class RetireController extends Controller
{
    /**
     * Retire a stable.
     */
    public function __invoke(Stable $stable): RedirectResponse
    {
        $this->authorize('retire', $stable);

        try {
            RetireAction::run($stable);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('stables.index');
    }
}
