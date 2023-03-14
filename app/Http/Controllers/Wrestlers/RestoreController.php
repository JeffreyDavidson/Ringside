<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Wrestler;
use Illuminate\Http\RedirectResponse;

class RestoreController extends Controller
{
    /**
     * Restore a deleted wrestler.
     */
    public function __invoke(int $wrestlerId): RedirectResponse
    {
        $wrestler = Wrestler::onlyTrashed()->findOrFail($wrestlerId);

        $this->authorize('restore', $wrestler);

        RestoreAction::run($wrestler);

        return to_route('wrestlers.index');
    }
}
