<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Models\Users\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ShowController
{
    public function __invoke(User $user): View
    {
        Gate::authorize('view', $user);

        return view('users.show', [
            'user' => $user,
        ]);
    }
}