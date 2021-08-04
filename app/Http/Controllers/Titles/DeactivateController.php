<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\DeactivateRequest;
use App\Models\Title;
use App\Services\TitleService;

class DeactivateController extends Controller
{
    /**
     * Deactivates a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\DeactivateRequest $request
     * @param  \App\Services\TitleService $titleService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, DeactivateRequest $request, TitleService $titleService)
    {
        $titleService->deactivate($title);

        return redirect()->route('titles.index');
    }
}
