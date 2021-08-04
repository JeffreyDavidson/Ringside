<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\ActivateRequest;
use App\Models\Title;
use App\Services\TitleService;

class ActivateController extends Controller
{
    /**
     * Activates a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\ActivateRequest $request
     * @param  \App\Services\TitleService $titleService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, ActivateRequest $request, TitleService $titleService)
    {
        $titleService->activate($title);

        return redirect()->route('titles.index');
    }
}
