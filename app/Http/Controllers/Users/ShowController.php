<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Models\Users\User;
use Illuminate\Contracts\View\View;

class ShowController
{
    public function __invoke(User $user): View
    {
        return view('users.show', [
            'user' => $user,
        ]);
    }
}
