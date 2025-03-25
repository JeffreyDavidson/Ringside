<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class WrestlersController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewList', Wrestler::class);

        return view('wrestlers.index');
    }

    public function show(Wrestler $wrestler): View
    {
        Gate::authorize('view', $wrestler);

        return view('wrestlers.show', [
            'wrestler' => $wrestler,
        ]);
    }
}
