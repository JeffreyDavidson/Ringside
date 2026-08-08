<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use Illuminate\Contracts\View\View;

class IndexController
{
    public function __invoke(): View
    {
        return view('referees.index');
    }
}
