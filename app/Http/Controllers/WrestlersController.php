<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class WrestlersController
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
