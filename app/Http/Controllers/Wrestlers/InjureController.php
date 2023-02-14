<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Controller;
use App\Models\Wrestler;
use Illuminate\Http\RedirectResponse;

class InjureController extends Controller
{
    /**
     * Injure a wrestler.
     */
    public function __invoke(Wrestler $wrestler): RedirectResponse
    {
        $this->authorize('injure', $wrestler);

        try {
            InjureAction::run($wrestler);
        } catch (CannotBeInjuredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('wrestlers.index');
    }
}
