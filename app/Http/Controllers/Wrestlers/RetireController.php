<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Models\Wrestler;
use Illuminate\Http\RedirectResponse;

class RetireController extends Controller
{
    /**
     * Retire a wrestler.
     */
    public function __invoke(Wrestler $wrestler): RedirectResponse
    {
        $this->authorize('retire', $wrestler);

        try {
            RetireAction::run($wrestler);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('wrestlers.index');
    }
}
