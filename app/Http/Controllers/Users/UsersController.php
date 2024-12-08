<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewList', User::class);

        return view('users.index');
    }
}
