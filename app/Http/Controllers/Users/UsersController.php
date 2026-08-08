<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Models\Users\User;
use Illuminate\Contracts\View\View;

class UsersController
{
    public function index(): View
    {
        return view('users.index');
    }

    public function show(User $user): View
    {
        return view('users.show', ['user' => $user]);
    }
}
