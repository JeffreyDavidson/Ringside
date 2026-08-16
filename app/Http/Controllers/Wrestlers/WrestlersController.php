<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Contracts\View\View;

class WrestlersController
{
    public function index(): View
    {
        return view('wrestlers.index');
    }

    public function show(Wrestler $wrestler): View
    {
        return view('wrestlers.show', ['wrestler' => $wrestler]);
    }
}
