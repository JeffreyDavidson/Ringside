<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class IndexController
{
    public function __invoke(): View
    {
        Gate::authorize('viewList', Wrestler::class);

        return view('wrestlers.index');
    }
}