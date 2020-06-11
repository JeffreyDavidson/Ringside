<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\DeactivateRequest;
use App\Models\Title;

class DeactivateController extends Controller
{
    public function __invoke(Title $title, DeactivateRequest $request)
    {
        $title->deactivate();

        return redirect()->route('titles.index');
    }
}
