<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Models\Users\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class IndexController
{
    public function __invoke(): View
    {
        Gate::authorize('viewList', User::class);

        return view('users.index');
    }
}
