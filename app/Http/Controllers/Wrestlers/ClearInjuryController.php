<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Wrestler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ClearInjuryController
{
    /**
     * Have a wrestler recover from an injury.
     */
    public function __invoke(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('clearFromInjury', $wrestler);

        try {
            resolve(ClearInjuryAction::class)->handle($wrestler);
        } catch (CannotBeClearedFromInjuryException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('wrestlers.index');
    }
}
