<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\DeactivateAction;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Controller;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;

class DeactivateController extends Controller
{
    /**
     * Deactivates a title.
     */
    public function __invoke(Title $title): RedirectResponse
    {
        $this->authorize('deactivate', $title);

        try {
            DeactivateAction::run($title);
        } catch (CannotBeDeactivatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('titles.index');
    }
}
