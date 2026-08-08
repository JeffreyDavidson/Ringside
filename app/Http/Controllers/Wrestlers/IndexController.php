<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use Illuminate\Contracts\View\View;

class IndexController
{
    public function __invoke(): View
    {
        return view('wrestlers.index');
    }
}
