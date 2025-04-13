<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Models\Wrestler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class UnretireController extends Controller
{
    /**
     * Unretire a wrestler.
     */
    public function __invoke(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('unretire', $wrestler);

        try {
            resolve(UnretireAction::class)->handle($wrestler);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('wrestlers.index');
    }
}
