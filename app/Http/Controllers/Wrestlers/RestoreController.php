<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\RestoreAction;
use App\Models\Wrestler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class RestoreController
{
    /**
     * Restore a deleted wrestler.
     */
    public function __invoke(int $wrestlerId): RedirectResponse
    {
        $wrestler = Wrestler::onlyTrashed()->findOrFail($wrestlerId);

        Gate::authorize('restore', $wrestler);

        resolve(RestoreAction::class)->handle($wrestler);

        return to_route('wrestlers.index');
    }
}
