<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\ActivateRequest;
use App\Models\Title;

class ActivateController extends Controller
{
    public function __invoke(Title $title, ActivateRequest $request)
    {
        $title->activate();

        return redirect()->route('titles.index');
    }
}
