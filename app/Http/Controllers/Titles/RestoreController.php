<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;

class RestoreController extends Controller
{
    /**
     * Restores a title.
     */
    public function __invoke(int $titleId): RedirectResponse
    {
        $title = Title::onlyTrashed()->findOrFail($titleId);

        $this->authorize('restore', $title);

        try {
            RestoreAction::run($title);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('titles.index');
    }
}
